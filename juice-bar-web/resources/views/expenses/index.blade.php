@extends('layouts.app')
@section('title', 'Expenses')
@php
    use App\Support\Money;
    $manage = auth()->user()->canManage();
    $active = $categories->where('is_active', true);
@endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-0">Expenses</h1>
        <div class="text-muted">{{ $date->format('l, d M Y') }}{{ $date->eq($today) ? ' · today' : '' }}</div>
    </div>
    @if ($manage)
        <form method="get" class="d-flex gap-2 align-items-center">
            <a class="btn btn-outline-secondary" href="{{ route('expenses.index', ['date' => $date->subDay()->format('Y-m-d')]) }}" aria-label="Previous day"><i class="bi bi-chevron-left"></i></a>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ $today->format('Y-m-d') }}" class="form-control" onchange="this.form.submit()" aria-label="Date">
            @if ($date->lt($today))
                <a class="btn btn-outline-secondary" href="{{ route('expenses.index', ['date' => $date->addDay()->format('Y-m-d')]) }}" aria-label="Next day"><i class="bi bi-chevron-right"></i></a>
            @endif
        </form>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="jb-card p-3 mb-3">
            <h2 class="h6 fw-bold">Add expense</h2>
            <form method="post" action="{{ route('expenses.store') }}" class="row g-2">
                @csrf
                @if ($manage)
                    <div class="col-6"><label class="form-label small mb-1" for="ex-date">Date</label>
                        <input type="date" id="ex-date" name="expense_date" class="form-control form-control-lg" value="{{ old('expense_date', $date->format('Y-m-d')) }}" max="{{ $today->format('Y-m-d') }}"></div>
                @endif
                <div class="{{ $manage ? 'col-6' : 'col-12' }}"><label class="form-label small mb-1" for="ex-amount">Amount (৳)</label>
                    <input type="number" id="ex-amount" name="amount" step="0.01" min="0.01" class="form-control form-control-lg" required value="{{ old('amount') }}" inputmode="decimal"></div>
                <div class="col-12">
                    <div class="form-label small mb-1">For</div>
                    <div class="opt-group">
                        @foreach ($active as $c)
                            <input type="radio" class="btn-check" name="expense_category_id" id="ec-{{ $c->id }}" value="{{ $c->id }}" @checked((string) old('expense_category_id') === (string) $c->id) required>
                            <label class="btn btn-outline-primary" for="ec-{{ $c->id }}">{{ $c->name }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="col-sm-5"><label class="form-label small mb-1" for="ex-method">Paid by</label>
                    <select id="ex-method" name="payment_method_id" class="form-select">
                        @foreach ($methods as $m)<option value="{{ $m->id }}" @selected((string) old('payment_method_id') === (string) $m->id)>{{ $m->name }}</option>@endforeach
                    </select></div>
                <div class="col-sm-7"><label class="form-label small mb-1" for="ex-note">Note</label>
                    <input id="ex-note" name="note" class="form-control" maxlength="255" placeholder="e.g. 5 kg mango, Karwan Bazar" value="{{ old('note') }}"></div>
                <div class="col-12"><button class="btn btn-primary btn-lg w-100"><i class="bi bi-plus-lg me-1"></i>Save expense</button></div>
            </form>
        </div>

        @if ($manage)
            <details class="jb-card p-3">
                <summary class="fw-bold">Expense categories</summary>
                <form method="post" action="{{ route('expenses.categories.store') }}" class="d-flex gap-2 mt-3">
                    @csrf
                    <input name="name" class="form-control" maxlength="60" required placeholder="New category" aria-label="New category">
                    <button class="btn btn-outline-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add</button>
                </form>
                <ul class="list-unstyled mb-0 mt-2">
                    @foreach ($categories as $c)
                        <li class="py-1 border-bottom">
                            <form method="post" action="{{ route('expenses.categories.update', $c) }}" class="d-flex gap-2 align-items-center">
                                @csrf @method('put')
                                <input name="name" class="form-control form-control-sm" value="{{ $c->name }}" maxlength="60" required aria-label="Name">
                                <div class="form-check form-switch m-0" title="Active"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($c->is_active) aria-label="Active"></div>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="jb-card p-3">
            <div class="d-flex justify-content-between align-items-end mb-2">
                <h2 class="h6 fw-bold mb-0">{{ $expenses->count() }} {{ \Illuminate\Support\Str::plural('expense', $expenses->count()) }}</h2>
                <div class="text-end"><div class="small text-muted">Total</div><div class="fs-4 fw-bold money text-danger">৳ {{ Money::format($total) }}</div></div>
            </div>
            @if ($byCategory->count() > 1)
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach ($byCategory as $name => $sum)
                        <span class="badge badge-soft-muted fw-normal fs-6">{{ $name }} <span class="money fw-semibold">৳ {{ Money::format($sum) }}</span></span>
                    @endforeach
                </div>
            @endif
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>For</th><th>Note</th><th>Paid by</th><th class="text-end">৳</th>@if($manage)<th></th>@endif</tr></thead>
                    <tbody>
                    @forelse ($expenses as $e)
                        <tr>
                            <td class="fw-semibold">{{ $e->category->name }}<div class="small text-muted fw-normal">{{ $e->created_at->setTimezone(config('pos.timezone'))->format('h:i A') }}@if($e->user) · {{ $e->user->name }}@endif</div></td>
                            <td>{{ $e->note }}</td>
                            <td>{{ $e->method?->name }}</td>
                            <td class="text-end money fw-semibold">{{ Money::format($e->amount) }}</td>
                            @if ($manage)
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-{{ $e->id }}" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                    <form method="post" action="{{ route('expenses.destroy', $e) }}" class="d-inline" onsubmit="return confirm('Delete this expense?')">@csrf @method('delete')
                                        <button class="btn btn-sm btn-outline-danger" aria-label="Delete"><i class="bi bi-trash"></i></button></form>
                                </td>
                            @endif
                        </tr>
                        @if ($manage)
                            <tr class="collapse" id="edit-{{ $e->id }}"><td colspan="5" class="bg-light">
                                <form method="post" action="{{ route('expenses.update', $e) }}" class="row g-2 align-items-end">
                                    @csrf @method('put')
                                    <div class="col-sm-3"><label class="form-label small mb-1">Date</label><input type="date" name="expense_date" class="form-control" value="{{ $e->expense_date->format('Y-m-d') }}" max="{{ $today->format('Y-m-d') }}"></div>
                                    <div class="col-sm-3"><label class="form-label small mb-1">For</label><select name="expense_category_id" class="form-select">
                                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($c->id === $e->expense_category_id) @disabled(! $c->is_active && $c->id !== $e->expense_category_id)>{{ $c->name }}</option>@endforeach</select></div>
                                    <div class="col-sm-2"><label class="form-label small mb-1">৳</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ $e->amount }}" required></div>
                                    <div class="col-sm-2"><label class="form-label small mb-1">Paid by</label><select name="payment_method_id" class="form-select">
                                        @foreach ($methods as $m)<option value="{{ $m->id }}" @selected($m->id === $e->payment_method_id)>{{ $m->name }}</option>@endforeach</select></div>
                                    <div class="col-sm-2"><button class="btn btn-primary w-100">Save</button></div>
                                    <div class="col-12"><input name="note" class="form-control" maxlength="255" value="{{ $e->note }}" placeholder="Note"></div>
                                </form>
                            </td></tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No expenses on this day.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
