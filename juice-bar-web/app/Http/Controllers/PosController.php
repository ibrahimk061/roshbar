<?php

namespace App\Http\Controllers;

use App\Models\HeldCart;
use App\Services\PosMenu;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(PosMenu $menu): View
    {
        $categories = $menu->categories();

        return view('pos.index', [
            'categories' => $categories,
            'products' => $menu->products($categories),
            'businessName' => $menu->businessName(),
            'methods' => $menu->methods(),
            'vatRate' => $menu->vatRate(),
            'vatInclusive' => $menu->vatInclusive(),
            'heldCount' => HeldCart::query()->count(),
        ]);
    }
}
