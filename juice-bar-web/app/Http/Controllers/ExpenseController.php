<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Services\BusinessDate;
use App\Services\ExpenseService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $today = CarbonImmutable::parse(BusinessDate::for(now())->format('Y-m-d'));
        $date = $request->filled('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date'))
            ? CarbonImmutable::parse($request->query('date'))
            : $today;
        // Cashiers see today only.
        if (! $request->user()->canManage()) {
            $date = $today;
        }

        $expenses = Expense::query()->with(['category', 'method', 'user'])
            ->whereBetween('expense_date', [$date->format('Y-m-d').' 00:00:00', $date->format('Y-m-d').' 23:59:59'])
            ->orderByDesc('id')->get();

        $total = $expenses->reduce(fn ($sum, Expense $e) => $sum->plus(Money::of($e->amount)), Money::zero());

        return view('expenses.index', [
            'date' => $date,
            'today' => $today,
            'expenses' => $expenses,
            'total' => Money::str($total),
            'byCategory' => $expenses->groupBy(fn ($e) => $e->category->name)
                ->map(fn ($rows) => Money::str($rows->reduce(fn ($s, $e) => $s->plus(Money::of($e->amount)), Money::zero())))
                ->sortDesc(),
            'categories' => ExpenseCategory::query()->ordered()->get(),
            'methods' => PaymentMethod::query()->active()->get(),
        ]);
    }

    public function store(ExpenseRequest $request, ExpenseService $service): RedirectResponse
    {
        $e = $service->save($request->validated(), $request->user());

        return redirect()->route('expenses.index', ['date' => $e->expense_date->format('Y-m-d')])
            ->with('status', '৳ '.Money::format($e->amount).' expense saved.');
    }

    public function update(ExpenseRequest $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $service->save($request->validated(), $request->user(), $expense);

        return redirect()->route('expenses.index', ['date' => $expense->expense_date->format('Y-m-d')])->with('status', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $date = $expense->expense_date->format('Y-m-d');
        $expense->delete();

        return redirect()->route('expenses.index', ['date' => $date])->with('status', 'Expense deleted.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', 'unique:expense_categories,name']]);
        ExpenseCategory::query()->create($data + ['sort_order' => (int) ExpenseCategory::query()->max('sort_order') + 1]);

        return back()->with('status', 'Category added.');
    }

    public function updateCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', Rule::unique('expense_categories', 'name')->ignore($category->id)]]);
        $category->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Category saved.');
    }
}
