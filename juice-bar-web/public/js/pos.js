/* Juice Bar POS screen. Totals here are display only; the server recalculates
   every price when the order is placed. Money is handled in integer paisa. */
(function () {
  'use strict';
  const P = window.POS;
  const $ = (s, el = document) => el.querySelector(s);
  const CART_KEY = 'jb.pos.cart.v1';

  const toPaisa = (s) => {
    const [i, d = ''] = String(s).split('.');
    const sign = i.trim().startsWith('-') ? -1 : 1;
    return sign * (Math.abs(parseInt(i, 10) || 0) * 100 + parseInt((d + '00').slice(0, 2), 10));
  };
  const fmt = (p) => {
    const neg = p < 0; p = Math.abs(p);
    const int = Math.floor(p / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return (neg ? '-' : '') + int + '.' + String(p % 100).padStart(2, '0');
  };
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const uuid = () => (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
    : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (crypto.getRandomValues(new Uint8Array(1))[0] & 15);
        return (c === 'x' ? r : (r & 3) | 8).toString(16);
      });

  const productsById = Object.fromEntries(P.products.map((p) => [p.id, p]));

  // ---------- state ----------
  let state = load() || { uuid: null, lines: [] };
  let activeCat = 'all';
  let query = '';

  function load() { try { return JSON.parse(localStorage.getItem(CART_KEY)); } catch (e) { return null; } }
  function save() { try { localStorage.setItem(CART_KEY, JSON.stringify(state)); } catch (e) { /* private mode */ } }

  // Drop lines whose product/variant no longer exists on the menu.
  state.lines = (state.lines || []).filter((l) => {
    const p = productsById[l.product_id];
    return p && p.variants.some((v) => v.id === l.variant_id);
  });

  // ---------- menu grid ----------
  function minPrice(p) { return Math.min(...p.variants.map((v) => toPaisa(v.price))); }

  function renderGrid() {
    const q = query.trim().toLowerCase();
    const items = P.products.filter((p) =>
      (activeCat === 'all' || String(p.category_id) === activeCat) &&
      (!q || p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)));

    const grid = $('#pos-grid');
    if (!items.length) {
      grid.innerHTML = '<div class="text-muted p-4" style="grid-column:1/-1">No items match. Try another search or category.</div>';
      return;
    }
    grid.innerHTML = items.map((p) => `
      <button type="button" class="pos-tile ${p.available ? '' : 'soldout'}" data-id="${p.id}" ${p.available ? '' : 'disabled'}>
        <div class="pos-tile-img">${p.image ? `<img src="${esc(p.image)}" alt="">` : esc(p.name.charAt(0))}</div>
        <div class="pos-tile-body">
          <div class="pos-tile-name">${esc(p.name)}</div>
          <div class="pos-tile-price money">${p.variants.length > 1 ? 'from ' : ''}৳ ${fmt(minPrice(p))}</div>
        </div>
      </button>`).join('');
  }

  $('#pos-cats').addEventListener('click', (e) => {
    const b = e.target.closest('.pos-cat'); if (!b) return;
    document.querySelectorAll('.pos-cat').forEach((x) => x.classList.toggle('active', x === b));
    activeCat = b.dataset.cat; renderGrid();
  });
  $('#pos-search').addEventListener('input', (e) => { query = e.target.value; renderGrid(); });
  $('#pos-grid').addEventListener('click', (e) => {
    const t = e.target.closest('.pos-tile'); if (!t || t.disabled) return;
    openItem(productsById[t.dataset.id]);
  });

  // ---------- item modal ----------
  const itemModal = new bootstrap.Modal('#item-modal');
  let draft = null;

  function chip(group, type, value, label, checked) {
    const id = `${group}-${value}`;
    return `<input type="${type}" class="btn-check" name="${group}" id="${id}" value="${esc(value)}" ${checked ? 'checked' : ''} autocomplete="off">
            <label class="btn btn-outline-primary" for="${id}">${label}</label>`;
  }

  function openItem(p) {
    const def = p.variants.find((v) => v.default) || p.variants[0];
    draft = { product: p, variant_id: def.id, sugar: P.defaultSugar, ice: P.defaultIce, addon_ids: [], quantity: 1, note: '' };
    $('#im-title').textContent = p.name;
    $('#im-variants').innerHTML = p.variants.map((v) => chip('v', 'radio', v.id, `${esc(v.name)}<br><small class="money">৳ ${fmt(toPaisa(v.price))}</small>`, v.id === def.id)).join('');
    $('#im-sugar').innerHTML = P.sugarLevels.map((s) => chip('s', 'radio', s, `${s}%`, s === draft.sugar)).join('');
    $('#im-ice').innerHTML = Object.entries(P.iceLevels).map(([k, l]) => chip('i', 'radio', k, esc(l), k === draft.ice)).join('');
    $('#im-addons-wrap').hidden = !p.addons.length;
    $('#im-addons').innerHTML = p.addons.map((a) => chip('a', 'checkbox', a.id, `${esc(a.name)} <small class="money">+৳${fmt(toPaisa(a.price))}</small>`, false)).join('');
    $('#im-note').value = '';
    updateDraft();
    itemModal.show();
  }

  function draftUnit() {
    const v = draft.product.variants.find((x) => x.id === draft.variant_id);
    const addons = draft.product.addons.filter((a) => draft.addon_ids.includes(a.id));
    return toPaisa(v.price) + addons.reduce((s, a) => s + toPaisa(a.price), 0);
  }
  function updateDraft() {
    $('#im-qty').textContent = draft.quantity;
    $('#im-total').textContent = fmt(draftUnit() * draft.quantity);
  }

  $('#item-modal').addEventListener('change', (e) => {
    if (!draft) return;
    const t = e.target;
    if (t.name === 'v') draft.variant_id = Number(t.value);
    if (t.name === 's') draft.sugar = t.value;
    if (t.name === 'i') draft.ice = t.value;
    if (t.name === 'a') draft.addon_ids = [...document.querySelectorAll('#im-addons input:checked')].map((x) => Number(x.value));
    updateDraft();
  });
  $('#im-minus').addEventListener('click', () => { draft.quantity = Math.max(1, draft.quantity - 1); updateDraft(); });
  $('#im-plus').addEventListener('click', () => { draft.quantity = Math.min(P.maxQty, draft.quantity + 1); updateDraft(); });
  $('#im-add').addEventListener('click', () => {
    draft.note = $('#im-note').value.trim();
    addLine(draft);
    itemModal.hide();
  });

  // ---------- cart ----------
  function lineKey(l) { return [l.variant_id, l.sugar, l.ice, [...l.addon_ids].sort().join('+'), l.note].join('|'); }

  function addLine(d) {
    if (!state.uuid) state.uuid = uuid();
    const line = { product_id: d.product.id, variant_id: d.variant_id, sugar: d.sugar, ice: d.ice, addon_ids: [...d.addon_ids], note: d.note, quantity: d.quantity };
    const same = state.lines.find((l) => lineKey(l) === lineKey(line));
    if (same) same.quantity = Math.min(P.maxQty, same.quantity + line.quantity);
    else state.lines.push(line);
    changed();
  }

  function lineView(l) {
    const p = productsById[l.product_id];
    const v = p.variants.find((x) => x.id === l.variant_id);
    const addons = p.addons.filter((a) => l.addon_ids.includes(a.id));
    const unit = toPaisa(v.price) + addons.reduce((s, a) => s + toPaisa(a.price), 0);
    return { p, v, addons, unit, total: unit * l.quantity };
  }

  function renderCart() {
    const box = $('#cart-lines');
    let total = 0, count = 0;
    if (!state.lines.length) {
      box.innerHTML = '<div class="cart-empty"><i class="bi bi-cup-straw fs-1 d-block mb-2"></i>Tap an item to start an order</div>';
    } else {
      box.innerHTML = state.lines.map((l, i) => {
        const x = lineView(l); total += x.total; count += l.quantity;
        const meta = [`Sugar ${l.sugar}%`, P.iceLevels[l.ice]].concat(x.addons.map((a) => '+ ' + a.name));
        return `<div class="cart-line" data-i="${i}">
          <div class="d-flex justify-content-between gap-2">
            <div><div class="fw-semibold">${esc(x.p.name)} <span class="text-muted fw-normal">(${esc(x.v.name)})</span></div>
              <div class="cart-line-meta">${meta.map(esc).join(' · ')}</div>
              ${l.note ? `<div class="cart-line-meta fst-italic">“${esc(l.note)}”</div>` : ''}</div>
            <div class="fw-bold money">৳ ${fmt(x.total)}</div>
          </div>
          <div class="d-flex align-items-center gap-2 mt-2">
            <button class="btn btn-outline-secondary qty-btn" data-act="dec" aria-label="Less"><i class="bi bi-dash"></i></button>
            <span class="qty-val">${l.quantity}</span>
            <button class="btn btn-outline-secondary qty-btn" data-act="inc" aria-label="More"><i class="bi bi-plus"></i></button>
            <span class="text-muted small ms-1 money">× ৳ ${fmt(x.unit)}</span>
            <button class="btn btn-link text-danger ms-auto" data-act="del" aria-label="Remove"><i class="bi bi-x-lg"></i></button>
          </div></div>`;
      }).join('');
    }
    $('#cart-total').textContent = fmt(total);
    $('#cart-count').textContent = count;
    $('#btn-checkout').disabled = !state.lines.length;
  }

  function changed() { save(); renderCart(); $('#checkout-error').textContent = ''; }

  $('#cart-lines').addEventListener('click', (e) => {
    const b = e.target.closest('[data-act]'); if (!b) return;
    const i = Number(b.closest('.cart-line').dataset.i);
    const l = state.lines[i];
    if (b.dataset.act === 'inc') l.quantity = Math.min(P.maxQty, l.quantity + 1);
    if (b.dataset.act === 'dec') { if (l.quantity > 1) l.quantity--; else state.lines.splice(i, 1); }
    if (b.dataset.act === 'del') state.lines.splice(i, 1);
    changed();
  });
  $('#btn-clear').addEventListener('click', () => {
    if (state.lines.length && confirm('Clear this order?')) { state = { uuid: null, lines: [] }; changed(); }
  });

  // ---------- checkout ----------
  const receiptModal = new bootstrap.Modal('#receipt-modal');
  let busy = false;

  $('#btn-checkout').addEventListener('click', async () => {
    if (busy || !state.lines.length) return;
    busy = true;
    const btn = $('#btn-checkout'); btn.disabled = true;
    const label = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
    if (!state.uuid) state.uuid = uuid();
    save();

    const payload = {
      uuid: state.uuid,
      order_type: document.querySelector('input[name=order_type]:checked').value,
      items: state.lines.map((l) => ({ variant_id: l.variant_id, quantity: l.quantity, sugar: l.sugar, ice: l.ice, addon_ids: l.addon_ids, note: l.note || null })),
    };

    try {
      const res = await fetch(P.storeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': P.csrf },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Could not save the order.');
        throw new Error(res.status === 419 ? 'Session expired. Reload the page and try again.' : first);
      }
      showReceipt(data);
      state = { uuid: null, lines: [] };
      save();
    } catch (err) {
      $('#checkout-error').textContent = err.message === 'Failed to fetch' ? 'No connection to the server. Your cart is kept; try again.' : err.message;
    } finally {
      busy = false; btn.innerHTML = label; renderCart();
    }
  });

  function showReceipt(data) {
    $('#rc-number').textContent = data.order_number;
    const frame = $('#rc-frame');
    frame.src = data.receipt_url + '?embed=1';
    frame.onload = () => { try { frame.contentWindow.focus(); frame.contentWindow.print(); } catch (e) { /* user can press Print */ } };
    receiptModal.show();
  }
  $('#rc-print').addEventListener('click', () => { const f = $('#rc-frame'); f.contentWindow.focus(); f.contentWindow.print(); });
  $('#rc-new').addEventListener('click', () => { receiptModal.hide(); const f = $('#rc-frame'); f.onload = null; f.src = 'about:blank'; renderCart(); });

  renderGrid();
  renderCart();
})();
