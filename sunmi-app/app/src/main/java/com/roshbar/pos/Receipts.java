package com.roshbar.pos;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;
import java.util.TimeZone;

/**
 * Receipts made on the device. Normal receipts come from the server; these are
 * only for orders taken offline (no order number yet) and the test print.
 */
final class Receipts {
    private static final int W = Printer.WIDTH;

    private Receipts() {}

    /** Receipt word from the server's menu (Bangla by default), with an English fallback. */
    private static String t(JSONObject menu, String key, String fallback) {
        JSONObject l = menu == null ? null : menu.optJSONObject("receipt_labels");
        String v = l == null ? null : l.optString(key, null);
        return v == null || v.isEmpty() ? fallback : v;
    }

    private static String sub(JSONObject menu, String group, String key, String fallback) {
        JSONObject l = menu == null ? null : menu.optJSONObject("receipt_labels");
        JSONObject g = l == null ? null : l.optJSONObject(group);
        String v = g == null ? null : g.optString(key, null);
        return v == null || v.isEmpty() ? fallback : v;
    }

    static JSONArray offline(JSONObject menu, List<Cart.Line> lines, Cart cart, JSONObject quote, String method, String tendered) throws JSONException {
        JSONArray out = new JSONArray();
        String cur = t(menu, "currency", "Tk");
        out.put(line(menu == null ? "Roshbar" : menu.optString("business_name", "Roshbar"), "center", true, 2));
        out.put(divider());
        out.put(line("OFFLINE", "center", true, 2));
        out.put(line("Number given when sent", "center", false, 1));
        SimpleDateFormat d = new SimpleDateFormat("dd-MM-yyyy", Locale.US);
        SimpleDateFormat tm = new SimpleDateFormat("hh:mm a", Locale.US);
        TimeZone dhaka = TimeZone.getTimeZone("Asia/Dhaka");
        d.setTimeZone(dhaka);
        tm.setTimeZone(dhaka);
        Date now = new Date();
        out.put(two(d.format(now), tm.format(now), false, 1));
        JSONObject types = menu == null ? null : menu.optJSONObject("order_types");
        String typeEn = types == null ? cart.orderType : types.optString(cart.orderType, cart.orderType);
        out.put(two(t(menu, "type", "Type"), sub(menu, "order_types", cart.orderType, typeEn), false, 1));
        out.put(divider());
        int items = 0;
        for (Cart.Line l : lines) {
            items += l.qty;
            out.put(line(l.product + " (" + l.size + ")", "left", true, 1));
            out.put(two(" " + l.qty + " x " + Money.fmt(l.unitWithAddons()), Money.fmt(l.total()), false, 1));
            StringBuilder meta = new StringBuilder();
            if (l.sugar != null) meta.append(t(menu, "sugar", "Sugar")).append(' ').append(l.sugar).append('%');
            if (l.ice != null) meta.append(meta.length() > 0 ? " | " : "").append(sub(menu, "ice_levels", l.ice, l.ice));
            if (meta.length() > 0) out.put(line("  " + meta, "left", false, 1));
            for (Cart.Addon a : l.addons) out.put(line("  + " + a.name, "left", false, 1));
            if (l.note != null && !l.note.isEmpty()) out.put(line("  * " + l.note, "left", false, 1));
        }
        out.put(divider());
        out.put(two(t(menu, "subtotal", "Subtotal"), Money.fmt(Money.paisa(quote.optString("subtotal"))), false, 1));
        if (Money.paisa(quote.optString("discount_total")) > 0) {
            out.put(two(t(menu, "discount", "Discount"), "-" + Money.fmt(Money.paisa(quote.optString("discount_total"))), false, 1));
        }
        if (Money.paisa(quote.optString("tax_total")) > 0) {
            out.put(two(t(menu, "vat", "VAT"), Money.fmt(Money.paisa(quote.optString("tax_total"))), false, 1));
        }
        out.put(divider());
        long grand = Money.paisa(quote.optString("grand_total"));
        out.put(two(t(menu, "total", "TOTAL"), cur + " " + Money.fmt(grand), true, 2));
        out.put(divider());
        out.put(two(method, Money.fmt(grand), false, 1));
        if (tendered != null && !tendered.isEmpty()) {
            long cash = Money.paisa(tendered);
            out.put(two(" " + t(menu, "cash_received", "Cash received"), Money.fmt(cash), false, 1));
            if (cash > grand) out.put(two(t(menu, "change", "CHANGE"), Money.fmt(cash - grand), true, 1));
        }
        out.put(line(t(menu, "paid", "PAID"), "center", true, 1));
        out.put(divider());
        out.put(line(t(menu, "items", "Items") + ": " + items, "center", false, 1));
        return out;
    }

    private static JSONObject two(String left, String right, boolean bold, int size) throws JSONException {
        JSONObject o = line(pair(left, right, size == 2 ? 21 : W), "left", bold, size);
        o.put("left", left);
        o.put("right", right);
        return o;
    }

    static JSONArray test(JSONObject menu) {
        JSONArray out = new JSONArray();
        try {
            out.put(line(menu == null ? "Roshbar" : menu.optString("business_name", "Roshbar"), "center", true, 2));
            out.put(line("Printer test", "center", false, 1));
            out.put(divider());
            out.put(line("12345678901234567890123456789012", "left", false, 1));
            out.put(two("বাম", "ডান", false, 1));
            out.put(two("সর্বমোট", "৳ 1,234.00", true, 2));
            out.put(divider());
            out.put(line("বাংলা লেখা ঠিক দেখালে প্রিন্টার ঠিক আছে", "center", false, 1));
        } catch (JSONException ignored) {
            // cannot happen with these values
        }
        return out;
    }

    private static JSONObject line(String text, String align, boolean bold, int size) throws JSONException {
        JSONObject o = new JSONObject();
        o.put("text", text);
        o.put("align", align);
        o.put("bold", bold);
        o.put("size", size);
        return o;
    }

    private static JSONObject divider() throws JSONException {
        return new JSONObject().put("divider", true);
    }

    static String pair(String left, String right, int width) {
        int room = Math.max(1, width - right.length() - 1);
        if (left.length() > room) left = left.substring(0, room);
        int gap = Math.max(1, width - left.length() - right.length());
        return left + Printer.repeat(' ', gap) + right;
    }
}
