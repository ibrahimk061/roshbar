<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Drop empty variant rows the form leaves behind, and map the chosen
        // default (a form row key) onto the position in the cleaned list.
        $rows = collect($this->input('variants', []))
            ->filter(fn ($v) => filled($v['name'] ?? null) || filled($v['price'] ?? null));
        $defaultPos = array_search((string) $this->input('default_variant', ''), array_map('strval', $rows->keys()->all()), true);

        $variants = $rows
            ->map(fn ($v) => [...$v, 'is_active' => filter_var($v['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)])
            ->values()
            ->all();

        $this->merge([
            'variants' => $variants,
            'default_variant' => $defaultPos === false ? 0 : $defaultPos,
            'is_active' => $this->boolean('is_active'),
            'is_available' => $this->boolean('is_available'),
            'is_seasonal' => $this->boolean('is_seasonal'),
            'is_featured' => $this->boolean('is_featured'),
            'show_on_website' => $this->boolean('show_on_website'),
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'color' => strtoupper((string) ($this->input('color') ?: '#FFB000')),
            'garnish' => $this->input('garnish') ?: 'orange',
            'sku' => strtoupper(trim((string) $this->input('sku'))),
        ]);
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
            'is_available' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'variants' => ['required', 'array', 'min:1', 'max:10'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'variants.*.price' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'default_variant' => ['nullable', 'integer', 'min:0'],
            // website
            'slug' => ['required', 'string', 'max:120', Rule::unique('products', 'slug')->ignore($productId)],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'ingredients' => ['nullable', 'string', 'max:255'],
            'ingredients_bn' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-F]{6}$/'],
            'garnish' => ['required', Rule::in(\App\Models\Product::GARNISHES)],
            'calories' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'is_seasonal' => ['boolean'],
            'season_months' => ['nullable', 'array'],
            'season_months.*' => ['integer', 'between:1,12'],
            'is_featured' => ['boolean'],
            'show_on_website' => ['boolean'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:addons,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required' => 'Add at least one size/variation with a price.',
            'variants.min' => 'Add at least one size/variation with a price.',
            'variants.*.name.required' => 'Each variation needs a name (e.g. Regular).',
            'variants.*.name.distinct' => 'Two variations have the same name.',
            'variants.*.price.required' => 'Each variation needs a price.',
            'variants.*.price.decimal' => 'Price can have at most 2 decimal places.',
            'slug.unique' => 'Another product already uses this web address (slug).',
            'color.regex' => 'Colour must look like #FFB000.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $active = collect($this->input('variants', []))->filter(fn ($row) => ! empty($row['is_active']));
            if ($this->input('variants') && $active->isEmpty()) {
                $v->errors()->add('variants', 'At least one variation must be active.');
            }
        }];
    }
}
