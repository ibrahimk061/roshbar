@extends('layouts.app')
@section('title', 'Message')
@php $wa = preg_replace('/\D/', '', $m->phone); if (str_starts_with($wa, '01')) { $wa = '88'.$wa; } @endphp
@section('content')
<a href="{{ route('admin.messages.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> All messages</a>
<div class="jb-card p-4" style="max-width:760px">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">{{ $m->name }}</h1>
            <div class="text-muted">{{ $m->phone }} · <span class="text-capitalize">{{ $m->topic }}</span> · {{ $m->created_at->setTimezone(config('pos.timezone'))->format('d-m-Y h:i A') }}</div>
        </div>
        <div class="d-flex gap-2 align-items-start">
            <a class="btn btn-primary" href="tel:{{ $m->phone }}"><i class="bi bi-telephone"></i> Call</a>
            <a class="btn btn-outline-primary" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>
        </div>
    </div>
    <p style="white-space:pre-line">{{ $m->message }}</p>
    <div class="d-flex gap-2 mt-4">
        <form method="post" action="{{ route('admin.messages.unread', $m) }}">@csrf<button class="btn btn-outline-secondary">Mark unread</button></form>
        <form method="post" action="{{ route('admin.messages.destroy', $m) }}" onsubmit="return confirm('Delete this message?')">@csrf @method('delete')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form>
    </div>
</div>
@endsection
