<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter menu = the roshbar.com starter data (database/seeders/data/roshbar_menu.json).
 * For the live menu use `php artisan roshbar:import-legacy` instead.
 */
class RoshbarMenuSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/roshbar_menu.json'), true, flags: JSON_THROW_ON_ERROR);

        $settings = collect($data['settings'])->pluck('value', 'key')->all() + [
            'business_name' => 'Roshbar',
            'order_prefix' => 'RB',
            'receipt_footer' => 'Thank you! Stay fresh. roshbar.com',
        ];
        foreach ($settings as $key => $value) {
            Setting::put($key, $value);
        }

        $addons = collect([['Honey', '30.00'], ['Chia Seeds', '25.00'], ['Extra Milk', '20.00'], ['Ice Cream', '50.00']])
            ->mapWithKeys(fn ($a, $i) => [$a[0] => Addon::query()->updateOrCreate(['name' => $a[0]], ['price' => $a[1], 'sort_order' => $i, 'is_active' => true])]);

        $categories = [];
        foreach ($data['categories'] as $c) {
            $categories[$c['id']] = Category::query()->updateOrCreate(['slug' => $c['slug']], [
                'name' => $c['name_en'], 'name_bn' => $c['name_bn'], 'sort_order' => $c['sort_order'], 'is_active' => true,
            ]);
        }

        $sizes = collect($data['product_sizes'])->groupBy('product_id');
        foreach ($data['products'] as $i => $p) {
            $category = $categories[$p['category_id']];
            $product = Product::query()->updateOrCreate(['slug' => $p['slug']], [
                'category_id' => $category->id,
                'name' => $p['name_en'],
                'name_bn' => $p['name_bn'],
                'sku' => Str::upper(Str::limit($p['slug'], 50, '')),
                'description' => $p['description_en'],
                'description_bn' => $p['description_bn'],
                'ingredients' => $p['ingredients_en'],
                'ingredients_bn' => $p['ingredients_bn'],
                'color' => $p['color'],
                'garnish' => $p['garnish'],
                'calories' => $p['calories'],
                'is_seasonal' => (bool) $p['is_seasonal'],
                'season_months' => $p['season_months'],
                'is_featured' => (bool) $p['is_featured'],
                'is_active' => (bool) $p['is_active'],
                'is_available' => true,
                'show_on_website' => true,
                'sort_order' => $p['sort_order'] ?: $i,
            ]);

            foreach ($sizes->get($p['id'], collect())->sortBy('price')->values() as $n => $s) {
                $product->variants()->updateOrCreate(['name' => $s['label']], [
                    'price' => \App\Support\Money::str(\App\Support\Money::of((string) $s['price'])),
                    'is_default' => $n === 0,
                    'is_active' => true,
                    'sort_order' => $n,
                ]);
            }

            $allowed = $category->slug === 'smoothies' ? ['Honey', 'Extra Milk', 'Ice Cream'] : ['Honey', 'Chia Seeds'];
            $product->addons()->sync($addons->only($allowed)->pluck('id'));
        }
    }
}
