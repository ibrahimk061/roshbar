<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // The quote endpoint sends the same cart without uuid/payments.
        $isQuote = $this->routeIs('orders.quote');

        return [
            'uuid' => [$isQuote ? 'nullable' : 'required', 'uuid'],
            'order_type' => ['required', Rule::in(array_keys(config('pos.order_types')))],
            'note' => ['nullable', 'string', 'max:500'],
            // Set by the POS when the order was taken offline and is being sent later.
            'created_offline_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('pos.max_quantity_per_line')],
            'items.*.sugar' => ['nullable', Rule::in(config('pos.sugar_levels'))],
            'items.*.ice' => ['nullable', Rule::in(array_keys(config('pos.ice_levels')))],
            'items.*.addon_ids' => ['nullable', 'array', 'max:20'],
            'items.*.addon_ids.*' => ['integer'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'array'],
            'discount.type' => ['nullable', Rule::in(['amount', 'percent'])],
            'discount.value' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999'],
            'discount.reason' => ['nullable', 'string', 'max:255'],
            ...PaymentRules::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'The cart is empty.',
            'items.min' => 'The cart is empty.',
            'discount.value.decimal' => 'Discount can have at most 2 decimal places.',
            ...PaymentRules::messages(),
        ];
    }
}
