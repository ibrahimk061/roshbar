@extends('layouts.app')
@section('title', 'Messages')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0">Website messages @if($unread)<span class="badge text-bg-warning fs-6 align-middle">{{ $unread }} unread</span>@endif</h1>
    <div class="btn-group">
        @foreach (['' => 'All', 'unread' => 'Unread', 'catering' => 'Catering', 'feedback' => 'Feedback', 'general' => 'General'] as $key => $label)
            <a href="{{ route('admin.messages.index', $key ? ['f' => $key] : []) }}" class="btn btn-outline-primary @if(($filter ?? '') === $key) active @endif">{{ $label }}</a>
        @endforeach
    </div>
</div>
<div class="jb-card">
    <div class="list-group list-group-flush">
        @forelse ($messages as $m)
            <a href="{{ route('admin.messages.show', $m) }}" class="list-group-item list-group-item-action py-3 {{ $m->is_read ? '' : 'fw-semibold' }}">
                <div class="d-flex justify-content-between gap-2">
                    <span>@unless($m->is_read)<span class="badge text-bg-warning me-1">New</span>@endunless{{ $m->name }} <span class="text-muted fw-normal">· {{ $m->phone }}</span></span>
                    <span class="text-muted small fw-normal text-nowrap">{{ $m->created_at->setTimezone(config('pos.timezone'))->format('d-m-Y h:i A') }}</span>
                </div>
                <div class="small text-muted fw-normal text-truncate"><span class="badge badge-soft-muted text-capitalize me-1">{{ $m->topic }}</span>{{ $m->message }}</div>
            </a>
        @empty
            <div class="text-center text-muted py-5">No messages here. Messages from the website contact form show up in this list.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $messages->links('pagination::bootstrap-5') }}</div>
@endsection
