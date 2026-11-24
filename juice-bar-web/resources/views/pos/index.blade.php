@extends('layouts.app')
@section('title', 'POS')

@section('fullbleed')
<div class="pos-shell" id="pos">
    {{-- Menu side --}}
    <section class="pos-menu">
        <div class="pos-toolbar d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width:420px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" id="pos-search" class="form-control form-control-lg" placeholder="Search item or code…" autocomplete="off">
            </div>
        </div>
        <div class="pos-cats" id="pos-cats">
            <button class="pos-cat active" data-cat="all">All</button>
            @foreach ($categories as $c)
                <button class="pos-cat" data-cat="{{ $c->id }}">{{ $c->name }}</button>
            @endforeach
        </div>
        <div class="pos-grid" id="pos-grid"></div>
    </section>

    {{-- Cart side --}}
    <aside class="pos-cart">
        <div class="cart-head d-flex align-items-center justify-content-between">
            <div class="fw-bold fs-5"><i class="bi bi-bag me-1"></i>Current order</div>
            <div class="btn-group" role="group" aria-label="Order type">
                @foreach (config('pos.order_types') as $key => $label)
                    <input type="radio" class="btn-check" name="order_type" id="ot-{{ $key }}" value="{{ $key }}" @checked($loop->first)>
                    <label class="btn btn-outline-primary" for="ot-{{ $key }}">{{ $label }}</label>
                @endforeach
            </div>
        </div>
        <div class="cart-lines" id="cart-lines"></div>
        <div class="cart-foot">
            <div class="d-flex justify-content-between text-muted small"><span>Items</span><span id="cart-count">0</span></div>
            <div class="d-flex justify-content-between align-items-end mb-3">
                <span class="fw-semibold">Total</span>
                <span class="cart-total money">৳ <span id="cart-total">0.00</span></span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-lg" id="btn-clear" title="Clear cart"><i class="bi bi-trash"></i></button>
                <button class="btn btn-primary btn-checkout flex-fill" id="btn-checkout" disabled>
                    <i class="bi bi-printer me-1"></i> Place order &amp; print
                </button>
            </div>
            <div class="text-danger small mt-2" id="checkout-error" role="alert"></div>
        </div>
    </aside>
</div>

{{-- Item options modal --}}
<div class="modal fade" id="item-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="im-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><div class="fw-semibold mb-2">Size</div><div class="opt-group" id="im-variants"></div></div>
                <div class="mb-3"><div class="fw-semibold mb-2">Sugar</div><div class="opt-group" id="im-sugar"></div></div>
                <div class="mb-3"><div class="fw-semibold mb-2">Ice</div><div class="opt-group" id="im-ice"></div></div>
                <div class="mb-3" id="im-addons-wrap"><div class="fw-semibold mb-2">Add-ons</div><div class="opt-group" id="im-addons"></div></div>
                <div class="mb-3">
                    <label class="fw-semibold mb-2" for="im-note">Note</label>
                    <input type="text" class="form-control form-control-lg" id="im-note" maxlength="255" placeholder="e.g. no mint, less sweet">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-semibold">Quantity</span>
                    <button class="btn btn-outline-secondary qty-btn" id="im-minus" type="button"><i class="bi bi-dash"></i></button>
                    <span class="qty-val fs-4" id="im-qty">1</span>
                    <button class="btn btn-outline-secondary qty-btn" id="im-plus" type="button"><i class="bi bi-plus"></i></button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-lg w-100 py-3 fw-bold" id="im-add">Add to order · ৳ <span id="im-total" class="money">0.00</span></button>
            </div>
        </div>
    </div>
</div>

{{-- Receipt preview modal --}}
<div class="modal fade" id="receipt-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle text-success me-1"></i>Order <span id="rc-number"></span></h5>
            </div>
            <div class="modal-body bg-light d-flex justify-content-center">
                <iframe id="rc-frame" title="Receipt preview" style="width:240px;height:460px;border:1px solid #ddd;background:#fff"></iframe>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-primary btn-lg" id="rc-print"><i class="bi bi-printer me-1"></i>Print</button>
                <button class="btn btn-primary btn-lg flex-fill" id="rc-new">New order</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.POS = {
        products: @json($products),
        sugarLevels: @json(config('pos.sugar_levels')),
        defaultSugar: @json(config('pos.default_sugar')),
        iceLevels: @json(config('pos.ice_levels')),
        defaultIce: @json(config('pos.default_ice')),
        maxQty: {{ (int) config('pos.max_quantity_per_line') }},
        storeUrl: @json(route('orders.store')),
        csrf: document.querySelector('meta[name=csrf-token]').content,
    };
</script>
<script src="{{ asset('js/pos.js') }}?v=1"></script>
@endpush
