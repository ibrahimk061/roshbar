@php
    $statusClass = ['new' => 'text-bg-primary', 'preparing' => 'text-bg-warning', 'ready' => 'text-bg-info', 'completed' => 'badge-soft-success', 'cancelled' => 'text-bg-secondary'][$o->status] ?? 'badge-soft-muted';
    $payClass = ['paid' => 'badge-soft-success', 'partial' => 'badge-soft-warning', 'unpaid' => 'badge-soft-warning'][$o->payment_status] ?? 'badge-soft-muted';
@endphp
<span class="badge {{ $statusClass }}">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span>
@if ($o->status !== 'cancelled')
    <span class="badge {{ $payClass }} text-capitalize">{{ $o->payment_status === 'paid' ? 'Paid' : ($o->payment_status === 'partial' ? 'Part paid' : 'Not paid') }}</span>
@endif
