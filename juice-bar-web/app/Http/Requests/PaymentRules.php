<?php

namespace App\Http\Requests;

/** Shape checks for payment rows; amounts and methods are checked in OrderService. */
final class PaymentRules
{
    public static function rules(): array
    {
        return [
            'payments' => ['nullable', 'array', 'max:5'],
            'payments.*.method' => ['required', 'string', 'max:30'],
            'payments.*.amount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999'],
            'payments.*.tendered' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function messages(): array
    {
        return [
            'payments.*.amount.decimal' => 'Amount can have at most 2 decimal places.',
            'payments.*.tendered.decimal' => 'Cash received can have at most 2 decimal places.',
        ];
    }
}
