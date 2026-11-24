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
        <div class="pos-tile-img" ${/^#[0-9A-Fa-f]{6}$/.test(p.color || '') ? `style="background:${p.color}"` : ''}>${p.image ? `<img src="${esc(p.image)}" alt="">` : esc(p.name.charAt(0))}</div>
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

  // ---------- checkout: quote from the server, then payment ----------
  const payModal = new bootstrap.Modal('#pay-modal');
  const receiptModal = new bootstrap.Modal('#receipt-modal');
  const methodsByCode = Object.fromEntries(P.methods.map((m) => [m.code, m]));
  let quote = null;      // server totals (strings)
  let payRows = [];      // [{method, amount, tendered, reference}]
  let busy = false;
  let quoteTimer = null;
  let quoteSeq = 0;

  const orderType = () => document.querySelector('input[name=order_type]:checked').value;
  const discountPayload = () => {
    if ($('#disc-box').hidden) return null;
    const value = $('#disc-value').value.trim();
    if (!value || toPaisa(value) <= 0) return null;
    return { type: document.querySelector('input[name=disc-type]:checked').value, value, reason: $('#disc-reason').value.trim() };
  };
  const cartPayload = () => ({
    order_type: orderType(),
    items: state.lines.map((l) => ({ variant_id: l.variant_id, quantity: l.quantity, sugar: l.sugar, ice: l.ice, addon_ids: l.addon_ids, note: l.note || null })),
    discount: discountPayload(),
  });

  async function post(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': P.csrf },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (res.status === 419) throw new Error('Session expired. Reload the page and try again.');
      if (res.status === 401) throw new Error('You were signed out. Reload the page and sign in.');
      throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Something went wrong.'));
    }
    return data;
  }
  const netError = (err) => (err.message === 'Failed to fetch' ? 'No connection to the server. Nothing was charged; try again.' : err.message);

  async function refreshQuote() {
    const seq = ++quoteSeq;
    $('#pay-confirm').disabled = true;
    try {
      const q = await post(P.quoteUrl, cartPayload());
      if (seq !== quoteSeq) return;
      quote = q;
      $('#pay-error').textContent = '';
    } catch (err) {
      if (seq !== quoteSeq) return;
      $('#pay-error').textContent = netError(err);
    }
    renderQuote();
  }
  const queueQuote = () => { clearTimeout(quoteTimer); quoteTimer = setTimeout(refreshQuote, 300); };

  function renderQuote() {
    const q = quote || { subtotal: '0', discount_total: '0', tax_total: '0', grand_total: '0', tax_rate: '0', tax_inclusive: false };
    $('#q-subtotal').textContent = fmt(toPaisa(q.subtotal));
    $('#q-discount').textContent = fmt(toPaisa(q.discount_total));
    $('#q-discount-row').hidden = toPaisa(q.discount_total) === 0;
    $('#q-tax').textContent = fmt(toPaisa(q.tax_total));
    $('#q-tax-row').hidden = toPaisa(q.tax_rate) === 0;
    $('#q-tax-label').textContent = `VAT ${q.tax_rate}%${q.tax_inclusive ? ' (included)' : ''}`;
    $('#q-total').textContent = fmt(toPaisa(q.grand_total));
    renderPayRows();
    $('#pay-confirm').disabled = !quote || busy || !payValid();
  }

  // The last row always covers what is left; earlier rows have typed amounts.
  function rowAmounts() {
    const total = quote ? toPaisa(quote.grand_total) : 0;
    let used = 0;
    return payRows.map((r, i) => {
      if (i === payRows.length - 1) return Math.max(0, total - used);
      const a = toPaisa(r.amount || '0'); used += a; return a;
    });
  }
  function payValid() {
    const amounts = rowAmounts();
    return payRows.every((r, i) => {
      const m = methodsByCode[r.method];
      if (!m || amounts[i] <= 0) return false;
      if (m.requires_reference && !r.reference.trim()) return false;
      if (m.is_cash && r.tendered !== '' && toPaisa(r.tendered) < amounts[i]) return false;
      return true;
    }) && payRows.filter((r) => methodsByCode[r.method]?.is_cash).length <= 1;
  }

  function renderPayRows() {
    const amounts = rowAmounts();
    const split = payRows.length > 1;
    $('#pay-rows').innerHTML = payRows.map((r, i) => {
      const m = methodsByCode[r.method] || {};
      const last = i === payRows.length - 1;
      const change = m.is_cash && r.tendered !== '' ? toPaisa(r.tendered) - amounts[i] : null;
      const quick = m.is_cash ? [amounts[i], Math.ceil(amounts[i] / 10000) * 10000, 50000, 100000]
        .filter((v, k, arr) => v >= amounts[i] && v > 0 && arr.indexOf(v) === k) : [];
      return `<div class="pay-row" data-i="${i}">
        <div class="opt-group mb-2">${P.methods.map((pm) => `
          <input type="radio" class="btn-check" name="pm-${i}" id="pm-${i}-${pm.code}" value="${esc(pm.code)}" ${pm.code === r.method ? 'checked' : ''}>
          <label class="btn btn-outline-primary" for="pm-${i}-${pm.code}">${esc(pm.name)}</label>`).join('')}
          ${split ? `<button type="button" class="btn btn-link text-danger ms-auto" data-act="del" aria-label="Remove"><i class="bi bi-x-lg"></i></button>` : ''}
        </div>
        <div class="row g-2 align-items-center">
          <div class="col-sm-5">${last
            ? `<div class="small text-muted">Amount</div><div class="fs-5 fw-bold money">৳ ${fmt(amounts[i])}</div>`
            : `<label class="small text-muted" for="pa-${i}">Amount</label><input type="number" step="0.01" min="0" class="form-control form-control-lg" id="pa-${i}" data-f="amount" value="${esc(r.amount)}">`}</div>
          ${m.is_cash ? `<div class="col-sm-7"><label class="small text-muted" for="pt-${i}">Cash received</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-lg" id="pt-${i}" data-f="tendered" value="${esc(r.tendered)}" placeholder="${fmt(amounts[i])}"></div>
            <div class="col-12 quick-cash d-flex gap-2 flex-wrap">${quick.map((v) => `<button type="button" class="btn btn-outline-secondary" data-cash="${v}">${v === amounts[i] ? 'Exact' : '৳ ' + fmt(v).replace('.00', '')}</button>`).join('')}</div>
            <div class="col-12">${change !== null ? (change >= 0
              ? `<span class="text-muted">Change</span> <span class="change-due money">৳ ${fmt(change)}</span>`
              : `<span class="text-danger">Cash received is less than ৳ ${fmt(amounts[i])}</span>`) : ''}</div>` : ''}
          ${m.requires_reference ? `<div class="col-sm-7"><label class="small text-muted" for="pr-${i}">${esc(m.name)} TrxID</label>
              <input type="text" class="form-control form-control-lg" id="pr-${i}" data-f="reference" value="${esc(r.reference)}" maxlength="100" autocomplete="off"></div>` : ''}
          ${!m.is_cash && !m.requires_reference ? `<div class="col-sm-7"><label class="small text-muted" for="pr-${i}">Reference (optional)</label>
              <input type="text" class="form-control form-control-lg" id="pr-${i}" data-f="reference" value="${esc(r.reference)}" maxlength="100" autocomplete="off"></div>` : ''}
        </div></div>`;
    }).join('');
    $('#pay-split').hidden = payRows.length >= 3;
  }

  // Typing in an input must not re-render (it would steal focus): update state, then refresh the parts around it.
  $('#pay-rows').addEventListener('input', (e) => {
    const f = e.target.dataset.f; if (!f) return;
    const i = Number(e.target.closest('.pay-row').dataset.i);
    payRows[i][f] = e.target.value;
    if (f === 'reference') { $('#pay-confirm').disabled = !quote || busy || !payValid(); return; }
    const pos = e.target.selectionStart; const id = e.target.id;
    renderPayRows();
    const el = document.getElementById(id); if (el) { el.focus(); try { el.setSelectionRange(pos, pos); } catch (x) { /* number input */ } }
    $('#pay-confirm').disabled = !quote || busy || !payValid();
  });
  $('#pay-rows').addEventListener('change', (e) => {
    if (!e.target.name?.startsWith('pm-')) return;
    const i = Number(e.target.closest('.pay-row').dataset.i);
    payRows[i] = { ...payRows[i], method: e.target.value, tendered: '', reference: '' };
    renderQuote();
  });
  $('#pay-rows').addEventListener('click', (e) => {
    const row = e.target.closest('.pay-row'); if (!row) return;
    const i = Number(row.dataset.i);
    const cash = e.target.closest('[data-cash]');
    if (cash) { payRows[i].tendered = (Number(cash.dataset.cash) / 100).toFixed(2); renderQuote(); }
    if (e.target.closest('[data-act=del]')) { payRows.splice(i, 1); renderQuote(); }
  });
  $('#pay-split').addEventListener('click', () => {
    const amounts = rowAmounts();
    // Freeze the current last row at half of what is left, the new row takes the rest.
    const last = payRows[payRows.length - 1];
    last.amount = (Math.floor(amounts[amounts.length - 1] / 2) / 100).toFixed(2);
    const other = P.methods.find((m) => m.code !== last.method && !m.is_cash) || P.methods[0];
    payRows.push({ method: other.code, amount: '', tendered: '', reference: '' });
    renderQuote();
  });
  $('#disc-toggle').addEventListener('click', () => {
    const box = $('#disc-box'); box.hidden = !box.hidden;
    $('#disc-toggle').innerHTML = box.hidden ? '<i class="bi bi-percent me-1"></i>Add discount' : '<i class="bi bi-x me-1"></i>Remove discount';
    if (!box.hidden) $('#disc-value').focus();
    queueQuote();
  });
  ['#disc-value', '#disc-reason'].forEach((s) => $(s).addEventListener('input', queueQuote));
  document.querySelectorAll('input[name=disc-type]').forEach((el) => el.addEventListener('change', queueQuote));

  $('#btn-checkout').addEventListener('click', () => {
    if (!state.lines.length) return;
    if (!state.uuid) { state.uuid = uuid(); save(); }
    quote = null;
    payRows = [{ method: (P.methods.find((m) => m.is_cash) || P.methods[0]).code, amount: '', tendered: '', reference: '' }];
    $('#disc-box').hidden = true; $('#disc-value').value = ''; $('#disc-reason').value = '';
    $('#disc-toggle').innerHTML = '<i class="bi bi-percent me-1"></i>Add discount';
    $('#pay-error').textContent = '';
    renderQuote();
    payModal.show();
    refreshQuote();
  });

  async function placeOrder(withPayment) {
    if (busy) return;
    busy = true;
    const btn = withPayment ? $('#pay-confirm') : $('#pay-later');
    const label = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
    const amounts = rowAmounts();
    const payload = {
      uuid: state.uuid,
      ...cartPayload(),
      payments: withPayment ? payRows.map((r, i) => ({
        method: r.method,
        amount: i === payRows.length - 1 ? null : (amounts[i] / 100).toFixed(2),
        tendered: methodsByCode[r.method]?.is_cash && r.tendered !== '' ? r.tendered : null,
        reference: r.reference.trim() || null,
      })) : [],
    };
    try {
      const data = await post(P.storeUrl, payload);
      payModal.hide();
      showReceipt(data);
      state = { uuid: null, lines: [] };
      save();
      renderCart();
    } catch (err) {
      $('#pay-error').textContent = netError(err);
    } finally {
      busy = false; btn.innerHTML = label; btn.disabled = false;
      $('#pay-confirm').disabled = !quote || !payValid();
    }
  }
  $('#pay-confirm').addEventListener('click', () => placeOrder(true));
  $('#pay-later').addEventListener('click', () => placeOrder(false));

  function showReceipt(data) {
    $('#rc-number').textContent = data.order_number;
    const change = toPaisa(data.change_total || '0');
    $('#rc-change').textContent = data.payment_status !== 'paid' ? 'Not paid yet' : (change > 0 ? `Change ৳ ${fmt(change)}` : '');
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
