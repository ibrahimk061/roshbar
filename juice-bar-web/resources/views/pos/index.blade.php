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
        <div class="cart-head d-flex align-items-center gap-2 flex-wrap">
            <div class="fw-bold fs-5 text-nowrap"><i class="bi bi-bag me-1"></i>Order</div>
            <button type="button" class="btn btn-sm net-pill" id="net-pill" hidden title="Orders saved offline"></button>
            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="btn-held" title="Orders on hold">
                <i class="bi bi-pause-circle me-1"></i>Held <span class="badge rounded-pill text-bg-primary" id="held-count">{{ $heldCount }}</span>
            </button>
            <div class="btn-group ms-auto" role="group" aria-label="Order type">
                @foreach (config('pos.order_types') as $key => $label)
                    <input type="radio" class="btn-check" name="order_type" id="ot-{{ $key }}" value="{{ $key }}" @checked($loop->first)>
                    <label class="btn btn-outline-primary text-nowrap" for="ot-{{ $key }}">{{ $label }}</label>
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
                <button class="btn btn-outline-primary btn-lg" id="btn-hold" title="Hold this order and serve the next customer" disabled><i class="bi bi-pause-fill"></i> Hold</button>
                <button class="btn btn-primary btn-checkout flex-fill" id="btn-checkout" disabled>
                    <i class="bi bi-cash-coin me-1"></i> Checkout
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
                @if (config('pos.ask_sugar_ice'))
                <div class="mb-3"><div class="fw-semibold mb-2">Sugar</div><div class="opt-group" id="im-sugar"></div></div>
                <div class="mb-3"><div class="fw-semibold mb-2">Ice</div><div class="opt-group" id="im-ice"></div></div>
                @endif
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

{{-- Payment modal --}}
<div class="modal fade" id="pay-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-1"></i>Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="pay-summary">
                            <div class="d-flex justify-content-between"><span>Subtotal</span><span class="money">৳ <span id="q-subtotal">0.00</span></span></div>
                            <div class="d-flex justify-content-between text-danger" id="q-discount-row" hidden><span>Discount</span><span class="money">− ৳ <span id="q-discount">0.00</span></span></div>
                            <div class="d-flex justify-content-between" id="q-tax-row" hidden><span id="q-tax-label">VAT</span><span class="money">৳ <span id="q-tax">0.00</span></span></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-end"><span class="fw-semibold">Total</span><span class="pay-total money">৳ <span id="q-total">0.00</span></span></div>
                        </div>
                        <button type="button" class="btn btn-link px-0 mt-2" id="disc-toggle"><i class="bi bi-percent me-1"></i>Add discount</button>
                        <div id="disc-box" hidden class="mt-1">
                            <div class="input-group mb-2">
                                <input type="radio" class="btn-check" name="disc-type" id="dt-amount" value="amount" checked>
                                <label class="btn btn-outline-primary" for="dt-amount">৳</label>
                                <input type="radio" class="btn-check" name="disc-type" id="dt-percent" value="percent">
                                <label class="btn btn-outline-primary" for="dt-percent">%</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg" id="disc-value" placeholder="0" aria-label="Discount">
                            </div>
                            <input type="text" class="form-control" id="disc-reason" maxlength="255" placeholder="Reason (required), e.g. regular customer">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="fw-semibold mb-2">Paid by</div>
                        <div id="pay-rows"></div>
                        <button type="button" class="btn btn-link px-0" id="pay-split"><i class="bi bi-plus-lg me-1"></i>Split payment</button>
                    </div>
                </div>
                <div class="text-danger mt-2" id="pay-error" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary btn-lg" id="pay-later" title="Save the order now, take payment later (e.g. dine-in)">Pay later</button>
                <button class="btn btn-primary btn-lg flex-fill py-3 fw-bold" id="pay-confirm"><i class="bi bi-printer me-1"></i>Confirm &amp; print</button>
            </div>
        </div>
    </div>
</div>

{{-- Hold modal --}}
<div class="modal fade" id="hold-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="hold-form">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pause-circle me-1"></i>Hold order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold" for="hold-label">Name (optional)</label>
                <input type="text" class="form-control form-control-lg" id="hold-label" maxlength="60" placeholder="e.g. Table 3, Rahim bhai" autocomplete="off">
                <div class="small text-muted mt-2">The cart is saved without an order number. Resume it any time from “Held”.</div>
                <div class="text-danger small mt-2" id="hold-error" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-lg w-100" id="hold-save"><i class="bi bi-pause-fill me-1"></i>Hold</button>
            </div>
        </form>
    </div>
</div>

{{-- Held orders list --}}
<div class="modal fade" id="held-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pause-circle me-1"></i>Orders on hold</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="held-list"></div>
            <div class="text-danger small px-3 pb-3" id="held-error" role="alert"></div>
        </div>
    </div>
</div>

{{-- Offline orders waiting to be sent --}}
<div class="modal fade" id="offline-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-upload me-1"></i>Offline orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" id="offline-note"></div>
                <div id="offline-list"></div>
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
                <span class="ms-auto fs-5 fw-bold text-success" id="rc-change"></span>
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
        askSugarIce: @json((bool) config('pos.ask_sugar_ice')),
        sugarLevels: @json(config('pos.sugar_levels')),
        defaultSugar: @json(config('pos.default_sugar')),
        iceLevels: @json(config('pos.ice_levels')),
        defaultIce: @json(config('pos.default_ice')),
        maxQty: {{ (int) config('pos.max_quantity_per_line') }},
        storeUrl: @json(route('orders.store')),
        quoteUrl: @json(route('orders.quote')),
        methods: @json($methods),
        heldUrl: @json(route('held.index')),
        csrfUrl: @json(route('pos.csrf')),
        vatRate: @json($vatRate),
        vatInclusive: @json($vatInclusive),
        businessName: @json($businessName),
        cashier: @json(auth()->user()->name),
        orderTypes: @json(config('pos.order_types')),
        csrf: document.querySelector('meta[name=csrf-token]').content,
    };
</script>
<script src="{{ asset('js/pos.js') }}?v=7"></script>
<script>
    // Offline support: cache the POS screen and its files (see public/sw.js).
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {}));
    }
</script>
@endpush
