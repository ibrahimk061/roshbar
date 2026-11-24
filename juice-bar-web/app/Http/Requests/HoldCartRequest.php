<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoldCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('pos.max_quantity_per_line');

        return [
            'label' => ['nullable', 'string', 'max:60'],
            'order_type' => ['required', Rule::in(array_keys(config('pos.order_types')))],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.variant_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', "max:$max"],
            'lines.*.sugar' => ['nullable', 'string', 'max:10'],
            'lines.*.ice' => ['nullable', 'string', 'max:20'],
            'lines.*.addon_ids' => ['nullable', 'array', 'max:20'],
            'lines.*.addon_ids.*' => ['integer'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
