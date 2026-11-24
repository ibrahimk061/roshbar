<?php

namespace Tests\Feature;

use App\Services\PosMenu;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_without_photo_get_a_matching_icon(): void
    {
        $this->seed(RoshbarMenuSeeder::class);
        $icons = app(PosMenu::class)->products(collect())->pluck('icon', 'name_en');

        $this->assertSame('🥭', $icons['Himsagar Mango']);
        $this->assertSame('🍉', $icons['Watermelon Mint']);
        $this->assertSame('🥥', $icons['Green Daab']);
        $this->assertSame('🥛', $icons['Mango Lassi']);
        $this->assertSame('🥬', $icons['Green Reset']);
        $this->assertSame('🍌', $icons['Banana Date Shake']);
        $this->assertSame('🥕', $icons['Beet Glow']);
    }
}
