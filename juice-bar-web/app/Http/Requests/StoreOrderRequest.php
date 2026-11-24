<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth & roles: Phase 2.
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'order_type' => ['required', Rule::in(array_keys(config('pos.order_types')))],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('pos.max_quantity_per_line')],
            'items.*.sugar' => ['nullable', Rule::in(config('pos.sugar_levels'))],
            'items.*.ice' => ['nullable', Rule::in(array_keys(config('pos.ice_levels')))],
            'items.*.addon_ids' => ['nullable', 'array', 'max:20'],
            'items.*.addon_ids.*' => ['integer'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'The cart is empty.',
            'items.min' => 'The cart is empty.',
        ];
    }
}
