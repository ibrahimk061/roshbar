<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddonController extends Controller
{
    public function index(): View
    {
        return view('admin.addons.index', [
            'addons' => Addon::query()->withCount('products')->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Addon::query()->create($this->validated($request));

        return back()->with('status', 'Add-on added.');
    }

    public function update(Request $request, Addon $addon): RedirectResponse
    {
        $addon->update($this->validated($request));

        return back()->with('status', 'Add-on updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
