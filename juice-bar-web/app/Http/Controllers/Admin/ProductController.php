<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'variants'])
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->orderBy('category_id')
            ->ordered()
            ->get();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['is_active' => true, 'is_available' => true, 'show_on_website' => true, 'color' => '#FFB000', 'garnish' => 'orange']));
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load('variants', 'addons'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(fn () => $this->save(new Product, $request));
        ActivityLogger::log('product.create', $product->name.': '.$this->prices($product), $product);

        return redirect()->route('admin.products.index')->with('status', "“{$product->name}” added.");
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $before = $this->prices($product);
        DB::transaction(fn () => $this->save($product, $request));
        $after = $this->prices($product->fresh());
        ActivityLogger::log('product.update', $product->name.($before === $after ? ' (prices unchanged)' : ': '.$before.' → '.$after), $product);

        return redirect()->route('admin.products.index')->with('status', "“{$product->name}” updated.");
    }

    private function prices(Product $product): string
    {
        return $product->variants()->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($v) => $v->name.' '.$v->price)->implode(', ');
    }

    /** Quick toggle from the list: sold out / back in stock. */
    public function toggleAvailability(Product $product): RedirectResponse
    {
        $product->update(['is_available' => ! $product->is_available]);

        return back()->with('status', $product->name.($product->is_available ? ' is available again.' : ' marked sold out.'));
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->ordered()->get(),
            'addons' => Addon::query()->ordered()->get(),
        ]);
    }

    private function save(Product $product, ProductRequest $request): Product
    {
        $data = $request->validated();

        $product->fill([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
            'is_available' => $data['is_available'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'slug' => $data['slug'],
            'name_bn' => $data['name_bn'] ?? null,
            'description_bn' => $data['description_bn'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'ingredients_bn' => $data['ingredients_bn'] ?? null,
            'color' => $data['color'],
            'garnish' => $data['garnish'],
            'calories' => (int) ($data['calories'] ?? 0),
            'is_seasonal' => $data['is_seasonal'],
            'season_months' => $data['is_seasonal'] && ! empty($data['season_months'])
                ? collect($data['season_months'])->map(fn ($m) => (int) $m)->unique()->sort()->implode(',')
                : null,
            'is_featured' => $data['is_featured'],
            'show_on_website' => $data['show_on_website'],
        ]);

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->image_path = null;
        }
        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->image_path = $request->file('image')->store('products', 'public');
        }
        $product->save();

        // Variants: update existing rows by id, create new ones, and deactivate
        // removed ones (never hard-delete: old orders may reference them).
        $defaultIndex = (int) ($data['default_variant'] ?? 0);
        $rows = array_values($data['variants']);
        $submittedIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        // Archive removed variants first so their names are free for new rows.
        $product->variants()->whereNotIn('id', $submittedIds)->where('name', 'not like', '% (old #%')->get()
            ->each(fn ($v) => $v->update([
                'is_active' => false,
                'is_default' => false,
                'name' => mb_substr($v->name, 0, 40).' (old #'.$v->id.')',
            ]));

        foreach ($rows as $i => $row) {
            $attrs = [
                'name' => trim($row['name']),
                'price' => Money::str(Money::of((string) $row['price'])),
                'is_active' => (bool) $row['is_active'],
                'is_default' => $i === $defaultIndex,
                'sort_order' => $i,
            ];
            $variant = ! empty($row['id']) ? $product->variants()->whereKey($row['id'])->first() : null;
            $variant ? $variant->update($attrs) : $product->variants()->create($attrs);
        }

        $product->addons()->sync($data['addon_ids'] ?? []);

        return $product;
    }
}
