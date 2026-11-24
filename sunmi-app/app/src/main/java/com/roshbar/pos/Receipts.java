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

    static JSONArray offline(JSONObject menu, List<Cart.Line> lines, Cart cart, JSONObject quote, String method, String tendered) throws JSONException {
        JSONArray out = new JSONArray();
        out.put(line(menu == null ? "Roshbar" : menu.optString("business_name", "Roshbar"), "center", true, 2));
        out.put(divider());
        out.put(line("OFFLINE ORDER", "center", true, 2));
        out.put(line("Number given when sent", "center", false, 1));
        SimpleDateFormat d = new SimpleDateFormat("dd-MM-yyyy", Locale.US);
        SimpleDateFormat t = new SimpleDateFormat("hh:mm a", Locale.US);
        TimeZone dhaka = TimeZone.getTimeZone("Asia/Dhaka");
        d.setTimeZone(dhaka);
        t.setTimeZone(dhaka);
        Date now = new Date();
        out.put(line(pair(d.format(now), t.format(now), W), "left", false, 1));
        JSONObject types = menu == null ? null : menu.optJSONObject("order_types");
        out.put(line(pair("Type", types == null ? cart.orderType : types.optString(cart.orderType, cart.orderType), W), "left", false, 1));
        out.put(divider());
        JSONObject ices = menu == null ? null : menu.optJSONObject("ice_levels");
        int items = 0;
        for (Cart.Line l : lines) {
            items += l.qty;
            out.put(line(cut(l.product + " (" + l.size + ")", W), "left", true, 1));
            out.put(line(pair(" " + l.qty + " x " + Money.fmt(l.unitWithAddons()), Money.fmt(l.total()), W), "left", false, 1));
            String opts = l.options(ices).replace(" · ", " | ");
            if (!opts.isEmpty()) out.put(line(cut("  " + opts, W), "left", false, 1));
        }
        out.put(divider());
        out.put(line(pair("Subtotal", Money.fmt(Money.paisa(quote.optString("subtotal"))), W), "left", false, 1));
        if (Money.paisa(quote.optString("discount_total")) > 0) {
            out.put(line(pair("Discount", "-" + Money.fmt(Money.paisa(quote.optString("discount_total"))), W), "left", false, 1));
        }
        if (Money.paisa(quote.optString("tax_total")) > 0) {
            out.put(line(pair("VAT", Money.fmt(Money.paisa(quote.optString("tax_total"))), W), "left", false, 1));
        }
        out.put(divider());
        long grand = Money.paisa(quote.optString("grand_total"));
        out.put(line(pair("TOTAL", "Tk " + Money.fmt(grand), 21), "left", true, 2));
        out.put(divider());
        out.put(line(pair(cut(method, 18), Money.fmt(grand), W), "left", false, 1));
        if (tendered != null && !tendered.isEmpty()) {
            long cash = Money.paisa(tendered);
            out.put(line(pair(" Cash received", Money.fmt(cash), W), "left", false, 1));
            if (cash > grand) out.put(line(pair("CHANGE", Money.fmt(cash - grand), W), "left", true, 1));
        }
        out.put(line("PAID", "center", true, 1));
        out.put(divider());
        out.put(line("Items: " + items, "center", false, 1));
        return out;
    }

    static JSONArray test(JSONObject menu) {
        JSONArray out = new JSONArray();
        try {
            out.put(line(menu == null ? "Roshbar" : menu.optString("business_name", "Roshbar"), "center", true, 2));
            out.put(line("Printer test", "center", false, 1));
            out.put(divider());
            out.put(line("12345678901234567890123456789012", "left", false, 1));
            out.put(line(pair("Left", "Right", W), "left", false, 1));
            out.put(line(pair("TOTAL", "Tk 1,234.00", 21), "left", true, 2));
            out.put(divider());
            out.put(line("If every line fits, the printer is set up.", "center", false, 1));
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

    private static String cut(String s, int width) {
        return s.length() > width ? s.substring(0, width) : s;
    }
}
