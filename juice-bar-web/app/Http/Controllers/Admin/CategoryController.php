<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->withCount('products')->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Category::query()->create($data + ['slug' => $this->uniqueSlug($data['name'])]);

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => "“{$category->name}” has products. Deactivate it instead, or move its products first."]);
        }
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category?->id)],
            'name_bn' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; Category::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
