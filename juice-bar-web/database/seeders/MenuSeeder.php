<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample menu for development. Prices are placeholders; the owner edits the
 * real menu from Admin > Products.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'business_name' => 'Juice Bar',
            'business_address' => 'Dhaka, Bangladesh',
            'business_phone' => '01XXXXXXXXX',
            'order_prefix' => 'RB',
            'receipt_footer' => 'Thank you! Stay fresh.',
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        $addons = collect([
            ['Extra Milk', '20.00'],
            ['Honey', '30.00'],
            ['Ice Cream', '50.00'],
            ['Extra Fruit', '40.00'],
            ['Chia Seeds', '25.00'],
        ])->mapWithKeys(fn ($a, $i) => [$a[0] => Addon::query()->updateOrCreate(
            ['name' => $a[0]], ['price' => $a[1], 'sort_order' => $i, 'is_active' => true]
        )]);

        // category => [ [name, sku, [variant => price], [addon names]] ]
        $menu = [
            'Fresh Juice' => [
                ['Mango Juice', 'JU-MNG', ['Regular' => '120', 'Medium' => '150', 'Large' => '180'], ['Honey', 'Extra Fruit', 'Chia Seeds']],
                ['Orange Juice', 'JU-ORG', ['Regular' => '130', 'Medium' => '160', 'Large' => '190'], ['Honey', 'Chia Seeds']],
                ['Lemon Juice', 'JU-LMN', ['Regular' => '60', 'Large' => '90'], ['Honey', 'Chia Seeds']],
                ['Watermelon Juice', 'JU-WTM', ['Regular' => '100', 'Medium' => '130', 'Large' => '160'], ['Honey', 'Extra Fruit']],
                ['Mixed Fruit Juice', 'JU-MIX', ['Regular' => '150', 'Medium' => '180', 'Large' => '220'], ['Honey', 'Extra Fruit', 'Chia Seeds']],
            ],
            'Smoothies' => [
                ['Banana Smoothie', 'SM-BAN', ['Regular' => '160', 'Large' => '210'], ['Honey', 'Extra Milk', 'Chia Seeds']],
                ['Berry Smoothie', 'SM-BER', ['Regular' => '220', 'Large' => '280'], ['Honey', 'Extra Milk', 'Chia Seeds']],
            ],
            'Milkshakes' => [
                ['Chocolate Milkshake', 'MS-CHO', ['Regular' => '180', 'Large' => '230'], ['Ice Cream', 'Extra Milk']],
                ['Mango Milkshake', 'MS-MNG', ['Regular' => '170', 'Large' => '220'], ['Ice Cream', 'Extra Milk']],
            ],
            'Lemonade' => [
                ['Mint Lemonade', 'LM-MNT', ['Regular' => '90', 'Large' => '120'], ['Honey']],
                ['Classic Lemonade', 'LM-CLS', ['Regular' => '70', 'Large' => '100'], ['Honey']],
            ],
            'Coffee' => [
                ['Cold Coffee', 'CF-CLD', ['Regular' => '150', 'Large' => '190'], ['Ice Cream', 'Extra Milk']],
                ['Iced Latte', 'CF-LAT', ['Regular' => '170', 'Large' => '210'], ['Extra Milk']],
            ],
            'Special Drinks' => [
                ['Signature Green Detox', 'SP-GRN', ['Regular' => '200'], ['Honey', 'Chia Seeds']],
                ['Falooda Special', 'SP-FAL', ['Regular' => '250'], ['Ice Cream', 'Extra Fruit']],
            ],
        ];

        $c = 0;
        foreach ($menu as $categoryName => $products) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $c++, 'is_active' => true],
            );

            foreach ($products as $p => [$name, $sku, $variants, $addonNames]) {
                $product = Product::query()->updateOrCreate(['sku' => $sku], [
                    'category_id' => $category->id,
                    'name' => $name,
                    'sort_order' => $p,
                    'is_active' => true,
                    'is_available' => true,
                ]);

                $v = 0;
                foreach ($variants as $variantName => $price) {
                    $product->variants()->updateOrCreate(['name' => $variantName], [
                        'price' => $price,
                        'is_default' => $v === 0,
                        'is_active' => true,
                        'sort_order' => $v++,
                    ]);
                }

                $product->addons()->sync($addons->only($addonNames)->pluck('id'));
            }
        }
    }
}
