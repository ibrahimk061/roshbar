package com.roshbar.pos;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;
import java.util.TimeZone;

/**
 * The cart on the device. Only IDs, quantities and options go to the server;
 * the prices here are for the screen and for offline receipts.
 */
final class Cart {
    static final class Addon {
        final long id;
        final String name;
        final long price;

        Addon(long id, String name, long price) {
            this.id = id;
            this.name = name;
            this.price = price;
        }
    }

    static final class Line {
        final long variantId;
        final String product;
        final String size;
        final long unitPrice;
        final List<Addon> addons;
        final String sugar; // null = not asked
        final String ice;
        final String note;
        int qty;

        Line(long variantId, String product, String size, long unitPrice, List<Addon> addons, String sugar, String ice, String note, int qty) {
            this.variantId = variantId;
            this.product = product;
            this.size = size;
            this.unitPrice = unitPrice;
            this.addons = addons;
            this.sugar = sugar;
            this.ice = ice;
            this.note = note;
            this.qty = qty;
        }

        long unitWithAddons() {
            long t = unitPrice;
            for (Addon a : addons) t += a.price;
            return t;
        }

        long total() {
            return unitWithAddons() * qty;
        }

        /** Same item with the same options: add to the quantity instead of a new line. */
        boolean sameAs(Line o) {
            if (o.variantId != variantId || !eq(o.sugar, sugar) || !eq(o.ice, ice) || !eq(o.note, note) || o.addons.size() != addons.size()) return false;
            for (int i = 0; i < addons.size(); i++) if (addons.get(i).id != o.addons.get(i).id) return false;
            return true;
        }

        String options(JSONObject iceLabels) {
            StringBuilder b = new StringBuilder();
            if (sugar != null) b.append("Sugar ").append(sugar).append('%');
            if (ice != null) {
                if (b.length() > 0) b.append(" · ");
                b.append(iceLabels != null ? iceLabels.optString(ice, ice) : ice);
            }
            for (Addon a : addons) b.append(b.length() > 0 ? " · " : "").append("+ ").append(a.name);
            if (note != null && !note.isEmpty()) b.append(b.length() > 0 ? " · " : "").append("* ").append(note);
            return b.toString();
        }
    }

    final List<Line> lines = new ArrayList<>();
    String orderType = "takeaway";
    String discountType;   // "amount" | "percent" | null
    String discountValue;  // "10" / "50.00"
    String discountReason;

    void add(Line l) {
        for (Line x : lines) {
            if (x.sameAs(l)) {
                x.qty = Math.min(99, x.qty + l.qty);
                return;
            }
        }
        lines.add(l);
    }

    void clear() {
        lines.clear();
        discountType = null;
        discountValue = null;
        discountReason = null;
    }

    boolean isEmpty() {
        return lines.isEmpty();
    }

    int count() {
        int n = 0;
        for (Line l : lines) n += l.qty;
        return n;
    }

    long subtotal() {
        long s = 0;
        for (Line l : lines) s += l.total();
        return s;
    }

    boolean hasDiscount() {
        return discountType != null && Money.paisa(discountValue) > 0;
    }

    /** The body for /orders/quote; add uuid + payments for /orders. */
    JSONObject payload() throws JSONException {
        JSONObject o = new JSONObject();
        o.put("order_type", orderType);
        JSONArray items = new JSONArray();
        for (Line l : lines) {
            JSONObject i = new JSONObject();
            i.put("variant_id", l.variantId);
            i.put("quantity", l.qty);
            if (l.sugar != null) i.put("sugar", l.sugar);
            if (l.ice != null) i.put("ice", l.ice);
            JSONArray ids = new JSONArray();
            for (Addon a : l.addons) ids.put(a.id);
            i.put("addon_ids", ids);
            if (l.note != null && !l.note.isEmpty()) i.put("note", l.note);
            items.put(i);
        }
        o.put("items", items);
        if (hasDiscount()) {
            JSONObject d = new JSONObject();
            d.put("type", discountType);
            d.put("value", discountValue);
            d.put("reason", discountReason == null ? "" : discountReason);
            o.put("discount", d);
        }
        return o;
    }

    /**
     * Totals without the server (offline), same rounding as the web POS:
     * percent discount and VAT rounded half up to the paisa.
     */
    JSONObject localQuote(String vatRate, boolean vatInclusive) throws JSONException {
        long sub = subtotal();
        long disc = 0;
        if (hasDiscount()) {
            long v = Money.paisa(discountValue);
            disc = "percent".equals(discountType) ? Money.divRound(sub * v, 10000) : v;
            disc = Math.max(0, Math.min(disc, sub));
        }
        long net = sub - disc;
        long r = Money.paisa(vatRate);
        long tax = 0;
        long grand = net;
        if (r > 0 && vatInclusive) {
            tax = Money.divRound(net * r, 10000 + r);
        } else if (r > 0) {
            tax = Money.divRound(net * r, 10000);
            grand = net + tax;
        }
        JSONObject q = new JSONObject();
        q.put("subtotal", Money.str(sub));
        q.put("discount_total", Money.str(disc));
        q.put("tax_total", Money.str(tax));
        q.put("grand_total", Money.str(grand));
        q.put("offline", true);
        return q;
    }

    static String utcNow() {
        SimpleDateFormat f = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US);
        f.setTimeZone(TimeZone.getTimeZone("UTC"));
        return f.format(new Date());
    }

    private static boolean eq(String a, String b) {
        return a == null ? b == null : a.equals(b);
    }
}
