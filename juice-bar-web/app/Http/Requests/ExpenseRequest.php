<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['nullable', 'date_format:Y-m-d'],
            // New expenses need an active category; an old one can keep its (now hidden) category.
            'expense_category_id' => ['required', $this->route('expense')
                ? Rule::exists('expense_categories', 'id')
                : Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999', 'decimal:0,2'],
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'Choose what the money was spent on.',
            'amount.gt' => 'Amount must be more than 0.',
        ];
    }
}
