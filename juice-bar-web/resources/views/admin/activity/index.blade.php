@extends('layouts.app')
@section('title', 'Activity')
@php $tz = config('pos.timezone'); @endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 fw-bold mb-0">Activity</h1><div class="text-muted">Who did what: sign-ins, discounts, cancels, expenses, menu and settings changes.</div></div>
    <form method="get" class="d-flex flex-wrap gap-2">
        <select name="user" class="form-select w-auto" onchange="this.form.submit()" aria-label="Staff">
            <option value="">All staff</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>@endforeach
        </select>
        <select name="action" class="form-select w-auto" onchange="this.form.submit()" aria-label="Action">
            <option value="">All actions</option>
            @foreach (\App\Models\ActivityLog::ACTIONS as $k => $l)<option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>@endforeach
        </select>
    </form>
</div>
<div class="jb-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>When</th><th>Who</th><th>What</th><th>Details</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="text-nowrap small">{{ $log->created_at->setTimezone($tz)->format('d-m-Y h:i A') }}</td>
                    <td class="text-nowrap">{{ $log->user?->name ?? '—' }}</td>
                    <td class="text-nowrap"><span class="badge {{ in_array($log->action, ['order.cancel', 'expense.delete', 'login.failed'], true) ? 'badge-soft-warning' : 'badge-soft-muted' }}">{{ \App\Models\ActivityLog::ACTIONS[$log->action] ?? $log->action }}</span></td>
                    <td>
                        @if ($log->subject_type === 'order' && $log->subject_id)
                            <a href="{{ route('orders.show', $log->subject_id) }}">{{ $log->description }}</a>
                        @else
                            {{ $log->description }}
                        @endif
                        @if ($log->ip)<span class="text-muted small"> · {{ $log->ip }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">Nothing yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
