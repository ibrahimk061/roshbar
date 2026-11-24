<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    /**
     * Cashiers record today's spending only; owner/manager can pick any date
     * up to today (e.g. entering yesterday's bazar bill).
     */
    public function save(array $data, User $user, ?Expense $expense = null): Expense
    {
        $today = CarbonImmutable::parse(BusinessDate::for(now())->format('Y-m-d'));
        $date = $user->canManage() && ! empty($data['expense_date'])
            ? CarbonImmutable::parse($data['expense_date'])
            : ($expense ? CarbonImmutable::parse($expense->expense_date) : $today);
        if ($date->gt($today)) {
            throw ValidationException::withMessages(['expense_date' => 'The date cannot be in the future.']);
        }

        $values = [
            'expense_date' => $date->format('Y-m-d'),
            'expense_category_id' => (int) $data['expense_category_id'],
            'amount' => Money::str(Money::of((string) $data['amount'])),
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'note' => isset($data['note']) ? (trim($data['note']) ?: null) : null,
        ];

        if ($expense) {
            $expense->update($values);

            return $expense;
        }

        return Expense::query()->create($values + ['user_id' => $user->id]);
    }
}
