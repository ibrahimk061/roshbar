<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HeldCart;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->where('is_active', true)->ordered()->get(['id', 'name']);

        $products = Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true),
                'addons' => fn ($q) => $q->where('is_active', true),
            ])
            ->ordered()
            ->get()
            ->filter(fn (Product $p) => $p->variants->isNotEmpty())
            // "All" tab: group by category order, then product order.
            ->sortBy(fn (Product $p) => [$categories->search(fn ($c) => $c->id === $p->category_id), $p->sort_order, $p->name])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'sku' => $p->sku,
                'image' => $p->imageUrl(),
                'color' => $p->color,
                'available' => $p->is_available,
                'variants' => $p->variants->map(fn ($v) => [
                    'id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'default' => $v->is_default,
                ])->values(),
                'addons' => $p->addons->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->name, 'price' => $a->price,
                ])->values(),
            ])
            ->values();

        return view('pos.index', [
            'categories' => $categories,
            'products' => $products,
            'businessName' => Setting::get('business_name', config('pos.business_name')),
            'methods' => PaymentMethod::query()->active()->get(['code', 'name', 'is_cash', 'requires_reference']),
            'vatRate' => (string) Setting::get('vat_rate', '0'),
            'vatInclusive' => (bool) Setting::get('vat_inclusive', false),
            'heldCount' => HeldCart::query()->count(),
        ]);
    }
}
