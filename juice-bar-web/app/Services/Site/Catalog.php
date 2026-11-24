<?php

namespace App\Services\Site;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reads the shared menu (the same products the POS sells) and shapes it into
 * the row arrays the website views expect (name_en / name_bn, from_price ...).
 */
class Catalog
{
    private function base(): Builder
    {
        return Product::query()
            ->onWebsite()
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->select('products.*')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('categories.sort_order')
            ->orderBy('products.sort_order')
            ->orderBy('products.id');
    }

    public function featured(int $limit = 8): array
    {
        return $this->rows($this->base()->where('products.is_featured', true)->limit($limit)->get());
    }

    public function all(?string $categorySlug = null): array
    {
        return $this->rows($this->base()
            ->when($categorySlug, fn ($q) => $q->where('categories.slug', $categorySlug))
            ->get());
    }

    public function inSeason(int $month): array
    {
        return $this->rows($this->base()->where('products.is_seasonal', true)->get()
            ->filter(fn (Product $p) => in_array($month, $p->seasonMonths(), true)));
    }

    public function findBySlug(string $slug): ?array
    {
        $p = $this->base()->where('products.slug', $slug)->first();

        return $p ? $this->row($p) : null;
    }

    public function related(array $product, int $limit = 4): array
    {
        return $this->rows($this->base()
            ->where('products.id', '<>', $product['id'])
            ->reorder()
            ->orderByRaw('(products.category_id = ?) desc', [$product['category_id']])
            ->orderByDesc('products.is_featured')
            ->orderBy('products.id')
            ->limit($limit)
            ->get());
    }

    public function categories(): array
    {
        return Category::query()->where('is_active', true)->ordered()->get()
            ->map(fn (Category $c) => $this->categoryRow($c))->all();
    }

    /** Categories with active website product counts and a sample colour/garnish for the tile. */
    public function categoriesWithCount(): array
    {
        $products = $this->base()->get()->groupBy('category_id');

        return Category::query()->where('is_active', true)->ordered()->get()
            ->map(function (Category $c) use ($products) {
                $list = $products->get($c->id, collect());
                $sample = $list->sortByDesc('is_featured')->first();

                return $this->categoryRow($c) + [
                    'total' => $list->count(),
                    'color' => $sample->color ?? '#FFB000',
                    'garnish' => $sample->garnish ?? 'orange',
                ];
            })->all();
    }

    private function categoryRow(Category $c): array
    {
        return ['id' => $c->id, 'slug' => $c->slug, 'name_en' => $c->name, 'name_bn' => $c->name_bn];
    }

    private function rows(Collection $products): array
    {
        return $products->map(fn (Product $p) => $this->row($p))->values()->all();
    }

    private function row(Product $p): array
    {
        $sizes = $p->variants->sortBy('price', SORT_NUMERIC)->values();

        return [
            'id' => $p->id,
            'category_id' => $p->category_id,
            'slug' => $p->slug,
            'name_en' => $p->name,
            'name_bn' => $p->name_bn,
            'description_en' => $p->description,
            'description_bn' => $p->description_bn,
            'ingredients_en' => $p->ingredients,
            'ingredients_bn' => $p->ingredients_bn,
            'color' => $p->color ?: '#FFB000',
            'garnish' => $p->garnish ?: 'orange',
            'image' => $p->image_path ? 'storage/'.$p->image_path : null,
            'calories' => $p->calories,
            'is_seasonal' => $p->is_seasonal,
            'season_months' => $p->season_months,
            'is_featured' => $p->is_featured,
            'category_slug' => $p->category->slug,
            'category_en' => $p->category->name,
            'category_bn' => $p->category->name_bn,
            'from_price' => $sizes->first()?->price ?? '0.00',
            'sizes' => $sizes->map(fn ($v) => ['label' => $v->name, 'price' => $v->price])->all(),
        ];
    }
}
