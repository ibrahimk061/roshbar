package com.roshbar.pos;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.InputType;
import android.view.Gravity;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.FrameLayout;
import android.widget.GridView;
import android.widget.HorizontalScrollView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/** Menu -> cart -> pay -> print, with an offline queue when the internet drops. */
public class PosActivity extends Activity {
    private static final int MENU_ORDERS = 1, MENU_OFFLINE = 2, MENU_REFRESH = 3, MENU_TEST = 4, MENU_SIGNOUT = 5;
    private static final long SYNC_EVERY_MS = 30_000;

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private final Handler main = new Handler(Looper.getMainLooper());
    private final Cart cart = new Cart();
    private final List<JSONObject> shown = new ArrayList<>();

    private Prefs prefs;
    private Api api;
    private JSONObject menu;
    private long categoryId = 0; // 0 = All
    private boolean offline;
    private boolean syncing;
    private boolean signedOut;

    private LinearLayout categoryBar;
    private ProductAdapter adapter;
    private TextView statusPill, cartSummary, emptyMenu;
    private Button cartButton;
    private View menuView;
    private ScrollView cartView;
    private LinearLayout cartBox;

    private final Runnable syncLoop = new Runnable() {
        @Override
        public void run() {
            sync(false);
            main.postDelayed(this, SYNC_EVERY_MS);
        }
    };

    @Override
    protected void onCreate(Bundle state) {
        super.onCreate(state);
        prefs = new Prefs(this);
        if (prefs.token() == null) {
            toLogin();
            return;
        }
        api = new Api(prefs.server(), prefs.token());
        Printer.bind(this);
        setTitle("Roshbar · " + prefs.userName());
        buildViews();
        menu = prefs.menu();
        renderMenu();
        loadMenu(false);
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (api == null) return;
        main.removeCallbacks(syncLoop);
        main.post(syncLoop);
        updateStatus();
    }

    @Override
    protected void onPause() {
        main.removeCallbacks(syncLoop);
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        io.shutdown();
        super.onDestroy();
    }

    // ---------------------------------------------------------------- layout

    private void buildViews() {
        FrameLayout root = new FrameLayout(this);

        LinearLayout m = new LinearLayout(this);
        m.setOrientation(LinearLayout.VERTICAL);

        statusPill = Ui.text(this, "", 14, true, Color.WHITE);
        statusPill.setGravity(Gravity.CENTER);
        statusPill.setPadding(0, Ui.dp(this, 4), 0, Ui.dp(this, 4));
        statusPill.setOnClickListener(v -> showOfflineOrders());
        m.addView(statusPill, new LinearLayout.LayoutParams(-1, -2));

        HorizontalScrollView hs = new HorizontalScrollView(this);
        hs.setHorizontalScrollBarEnabled(false);
        categoryBar = Ui.row(this);
        int p = Ui.dp(this, 8);
        categoryBar.setPadding(p, p, p, 0);
        hs.addView(categoryBar);
        m.addView(hs);

        FrameLayout gridBox = new FrameLayout(this);
        GridView grid = new GridView(this);
        grid.setNumColumns(2);
        grid.setHorizontalSpacing(p);
        grid.setVerticalSpacing(p);
        grid.setPadding(p, p, p, p);
        grid.setClipToPadding(false);
        adapter = new ProductAdapter();
        grid.setAdapter(adapter);
        grid.setOnItemClickListener((parent, view, pos, id) -> openItem(shown.get(pos)));
        gridBox.addView(grid);
        emptyMenu = Ui.text(this, "Loading menu…", 16, false, Ui.MUTED);
        emptyMenu.setGravity(Gravity.CENTER);
        gridBox.addView(emptyMenu, new FrameLayout.LayoutParams(-1, -1));
        m.addView(gridBox, new LinearLayout.LayoutParams(-1, 0, 1));

        LinearLayout bar = Ui.row(this);
        bar.setPadding(p * 2, p, p, p);
        bar.setBackgroundColor(Ui.GREEN_SOFT);
        cartSummary = Ui.text(this, "", 17, true, Ui.TEXT);
        bar.addView(cartSummary, Ui.weight(1));
        cartButton = Ui.primary(this, "Cart");
        cartButton.setOnClickListener(v -> showCart(true));
        bar.addView(cartButton, new LinearLayout.LayoutParams(Ui.dp(this, 140), -2));
        m.addView(bar, new LinearLayout.LayoutParams(-1, -2));

        menuView = m;
        root.addView(m);

        cartView = new ScrollView(this);
        cartView.setBackgroundColor(Color.WHITE);
        cartBox = Ui.column(this, 12);
        cartView.addView(cartBox);
        cartView.setVisibility(View.GONE);
        root.addView(cartView);

        setContentView(root);
        updateCartBar();
    }

    // ------------------------------------------------------------------ menu

    private void loadMenu(boolean announce) {
        io.execute(() -> {
            try {
                JSONObject fresh = api.get("/menu");
                prefs.saveMenu(fresh);
                runOnUiThread(() -> {
                    menu = fresh;
                    offline = false;
                    renderMenu();
                    updateStatus();
                    if (announce) toast("Menu updated");
                });
            } catch (IOException e) {
                runOnUiThread(() -> {
                    offline = true;
                    updateStatus();
                    if (menu == null) emptyMenu.setText("No internet and no saved menu yet.\nConnect once to load the menu.");
                });
            } catch (Api.ApiError e) {
                runOnUiThread(() -> handleApiError(e));
            }
        });
    }

    private void renderMenu() {
        if (menu == null) return;
        categoryBar.removeAllViews();
        addCategory(0, "All");
        JSONArray cats = menu.optJSONArray("categories");
        for (int i = 0; cats != null && i < cats.length(); i++) {
            JSONObject c = cats.optJSONObject(i);
            addCategory(c.optLong("id"), c.optString("name"));
        }
        shown.clear();
        JSONArray products = menu.optJSONArray("products");
        for (int i = 0; products != null && i < products.length(); i++) {
            JSONObject pr = products.optJSONObject(i);
            if (categoryId == 0 || pr.optLong("category_id") == categoryId) shown.add(pr);
        }
        adapter.notifyDataSetChanged();
        emptyMenu.setText(shown.isEmpty() ? "No items in this category." : "");
        emptyMenu.setVisibility(shown.isEmpty() ? View.VISIBLE : View.GONE);
    }

    private void addCategory(long id, String name) {
        Button b = Ui.chip(this, name, id == categoryId);
        b.setOnClickListener(v -> {
            categoryId = id;
            renderMenu();
        });
        categoryBar.addView(b, Ui.wrapMargin(this, 6));
    }

    private final class ProductAdapter extends BaseAdapter {
        @Override
        public int getCount() {
            return shown.size();
        }

        @Override
        public Object getItem(int i) {
            return shown.get(i);
        }

        @Override
        public long getItemId(int i) {
            return shown.get(i).optLong("id");
        }

        @Override
        public View getView(int i, View convert, ViewGroup parent) {
            JSONObject pr = shown.get(i);
            LinearLayout tile = Ui.column(PosActivity.this, 12);
            tile.setMinimumHeight(Ui.dp(PosActivity.this, 92));
            boolean available = pr.optBoolean("available", true);
            tile.setBackground(Ui.round(PosActivity.this, available ? tint(pr.optString("color")) : Color.parseColor("#F1F1F1"), Ui.BORDER, 12));
            TextView name = Ui.text(PosActivity.this, pr.optString("name"), 17, true, available ? Ui.TEXT : Ui.MUTED);
            name.setMaxLines(2);
            tile.addView(name);
            String price = available ? (pr.optJSONArray("variants").length() > 1 ? "from Tk " : "Tk ") + Money.fmt(minPrice(pr)) : "Sold out";
            tile.addView(Ui.text(PosActivity.this, price, 15, false, available ? Ui.GREEN : Ui.RED));
            return tile;
        }
    }

    private static long minPrice(JSONObject pr) {
        JSONArray vs = pr.optJSONArray("variants");
        long min = Long.MAX_VALUE;
        for (int i = 0; vs != null && i < vs.length(); i++) min = Math.min(min, Money.paisa(vs.optJSONObject(i).optString("price")));
        return min == Long.MAX_VALUE ? 0 : min;
    }

    /** Product colour as a very light tile background; white when missing. */
    private static int tint(String hex) {
        try {
            int c = Color.parseColor(hex);
            return Color.rgb(255 - (255 - Color.red(c)) / 8, 255 - (255 - Color.green(c)) / 8, 255 - (255 - Color.blue(c)) / 8);
        } catch (RuntimeException e) {
            return Color.WHITE;
        }
    }

    // -------------------------------------------------------- item options

    private void openItem(JSONObject pr) {
        if (!pr.optBoolean("available", true)) {
            toast(pr.optString("name") + " is sold out");
            return;
        }
        final JSONArray variants = pr.optJSONArray("variants");
        final JSONArray addons = pr.optJSONArray("addons");
        final long[] variant = {variants.optJSONObject(0).optLong("id")};
        for (int i = 0; i < variants.length(); i++) if (variants.optJSONObject(i).optBoolean("default")) variant[0] = variants.optJSONObject(i).optLong("id");
        final String[] sugar = {menu.optString("default_sugar", "100")};
        final String[] ice = {menu.optString("default_ice", "regular")};
        final List<Long> picked = new ArrayList<>();
        final int[] qty = {1};

        LinearLayout box = Ui.column(this, 16);

        if (variants.length() > 1) {
            box.addView(Ui.label(this, "Size"));
            List<Button> group = new ArrayList<>();
            LinearLayout row = flow(box);
            for (int i = 0; i < variants.length(); i++) {
                JSONObject v = variants.optJSONObject(i);
                Button b = Ui.chip(this, v.optString("name") + "  Tk " + Money.fmt(Money.paisa(v.optString("price"))), v.optLong("id") == variant[0]);
                b.setOnClickListener(x -> {
                    variant[0] = v.optLong("id");
                    for (Button o : group) Ui.setChip(this, o, o == b);
                });
                group.add(b);
                row = addWrapped(box, row, b);
            }
        }

        box.addView(Ui.label(this, "Sugar"));
        JSONArray sugars = menu.optJSONArray("sugar_levels");
        List<Button> sg = new ArrayList<>();
        LinearLayout srow = flow(box);
        for (int i = 0; sugars != null && i < sugars.length(); i++) {
            String s = sugars.optString(i);
            Button b = Ui.chip(this, s + "%", s.equals(sugar[0]));
            b.setOnClickListener(x -> {
                sugar[0] = s;
                for (Button o : sg) Ui.setChip(this, o, o == b);
            });
            sg.add(b);
            srow = addWrapped(box, srow, b);
        }

        box.addView(Ui.label(this, "Ice"));
        JSONObject ices = menu.optJSONObject("ice_levels");
        List<Button> ig = new ArrayList<>();
        LinearLayout irow = flow(box);
        if (ices != null) {
            Iterator<String> keys = ices.keys();
            while (keys.hasNext()) {
                String k = keys.next();
                Button b = Ui.chip(this, ices.optString(k), k.equals(ice[0]));
                b.setOnClickListener(x -> {
                    ice[0] = k;
                    for (Button o : ig) Ui.setChip(this, o, o == b);
                });
                ig.add(b);
                irow = addWrapped(box, irow, b);
            }
        }

        if (addons != null && addons.length() > 0) {
            box.addView(Ui.label(this, "Add-ons"));
            LinearLayout arow = flow(box);
            for (int i = 0; i < addons.length(); i++) {
                JSONObject a = addons.optJSONObject(i);
                Button b = Ui.chip(this, a.optString("name") + " +" + Money.fmt(Money.paisa(a.optString("price"))), false);
                b.setOnClickListener(x -> {
                    Long id = a.optLong("id");
                    boolean on = !picked.contains(id);
                    if (on) picked.add(id); else picked.remove(id);
                    Ui.setChip(this, b, on);
                });
                arow = addWrapped(box, arow, b);
            }
        }

        box.addView(Ui.label(this, "Quantity"));
        LinearLayout qrow = Ui.row(this);
        Button minus = Ui.outline(this, "−");
        TextView qv = Ui.text(this, "1", 22, true, Ui.TEXT);
        qv.setGravity(Gravity.CENTER);
        Button plus = Ui.outline(this, "+");
        minus.setOnClickListener(x -> { if (qty[0] > 1) qv.setText(String.valueOf(--qty[0])); });
        plus.setOnClickListener(x -> { if (qty[0] < menu.optInt("max_quantity", 99)) qv.setText(String.valueOf(++qty[0])); });
        qrow.addView(minus, new LinearLayout.LayoutParams(Ui.dp(this, 64), -2));
        qrow.addView(qv, new LinearLayout.LayoutParams(Ui.dp(this, 64), -2));
        qrow.addView(plus, new LinearLayout.LayoutParams(Ui.dp(this, 64), -2));
        box.addView(qrow);

        box.addView(Ui.label(this, "Note (optional)"));
        EditText note = Ui.input(this, "e.g. no straw", InputType.TYPE_CLASS_TEXT);
        box.addView(note, new LinearLayout.LayoutParams(-1, -2));

        ScrollView sv = new ScrollView(this);
        sv.addView(box);
        new AlertDialog.Builder(this)
                .setTitle(pr.optString("name"))
                .setView(sv)
                .setNegativeButton("Cancel", null)
                .setPositiveButton("Add to cart", (d, w) -> {
                    JSONObject v = find(variants, variant[0]);
                    List<Cart.Addon> list = new ArrayList<>();
                    for (int i = 0; addons != null && i < addons.length(); i++) {
                        JSONObject a = addons.optJSONObject(i);
                        if (picked.contains(a.optLong("id"))) list.add(new Cart.Addon(a.optLong("id"), a.optString("name"), Money.paisa(a.optString("price"))));
                    }
                    String n = note.getText().toString().trim();
                    cart.add(new Cart.Line(v.optLong("id"), pr.optString("name"), v.optString("name"), Money.paisa(v.optString("price")),
                            list, sugar[0], ice[0], n.isEmpty() ? null : n, qty[0]));
                    updateCartBar();
                })
                .show();
    }

    private static JSONObject find(JSONArray a, long id) {
        for (int i = 0; i < a.length(); i++) if (a.optJSONObject(i).optLong("id") == id) return a.optJSONObject(i);
        return a.optJSONObject(0);
    }

    /** Chip rows that wrap after 3 chips (simple flow layout). */
    private LinearLayout flow(LinearLayout box) {
        LinearLayout r = Ui.row(this);
        box.addView(r);
        return r;
    }

    private LinearLayout addWrapped(LinearLayout box, LinearLayout row, Button b) {
        if (row.getChildCount() >= 3) row = flow(box);
        row.addView(b, Ui.wrapMargin(this, 6));
        return row;
    }

    // ------------------------------------------------------------------ cart

    private void updateCartBar() {
        if (cartSummary == null) return;
        cartSummary.setText(cart.isEmpty() ? "Cart is empty" : cart.count() + " items · Tk " + Money.fmt(cart.subtotal()));
        cartButton.setEnabled(!cart.isEmpty());
        cartButton.setAlpha(cart.isEmpty() ? 0.5f : 1f);
    }

    private void showCart(boolean open) {
        if (open && cart.isEmpty()) return;
        menuView.setVisibility(open ? View.GONE : View.VISIBLE);
        cartView.setVisibility(open ? View.VISIBLE : View.GONE);
        if (open) renderCart();
        updateCartBar();
    }

    @Override
    public void onBackPressed() {
        if (cartView != null && cartView.getVisibility() == View.VISIBLE) {
            showCart(false);
        } else {
            super.onBackPressed();
        }
    }

    private void renderCart() {
        cartBox.removeAllViews();
        LinearLayout head = Ui.row(this);
        Button back = Ui.outline(this, "‹ Menu");
        back.setOnClickListener(v -> showCart(false));
        head.addView(back, new LinearLayout.LayoutParams(-2, -2));
        TextView title = Ui.text(this, "  Cart", 22, true, Ui.TEXT);
        head.addView(title, Ui.weight(1));
        cartBox.addView(head);

        JSONObject ices = menu == null ? null : menu.optJSONObject("ice_levels");
        for (int i = 0; i < cart.lines.size(); i++) {
            final Cart.Line l = cart.lines.get(i);
            LinearLayout row = Ui.row(this);
            row.setPadding(0, Ui.dp(this, 10), 0, Ui.dp(this, 10));
            LinearLayout info = Ui.column(this, 0);
            info.addView(Ui.text(this, l.product + " (" + l.size + ")", 16, true, Ui.TEXT));
            info.addView(Ui.text(this, l.options(ices), 13, false, Ui.MUTED));
            info.addView(Ui.text(this, l.qty + " × " + Money.fmt(l.unitWithAddons()) + " = Tk " + Money.fmt(l.total()), 15, false, Ui.GREEN));
            row.addView(info, Ui.weight(1));
            Button minus = Ui.outline(this, "−");
            minus.setOnClickListener(v -> {
                if (l.qty > 1) l.qty--; else cart.lines.remove(l);
                afterCartChange();
            });
            Button plus = Ui.outline(this, "+");
            plus.setOnClickListener(v -> {
                if (l.qty < 99) l.qty++;
                afterCartChange();
            });
            row.addView(minus, new LinearLayout.LayoutParams(Ui.dp(this, 52), -2));
            row.addView(plus, new LinearLayout.LayoutParams(Ui.dp(this, 52), -2));
            cartBox.addView(row);
            cartBox.addView(Ui.divider(this));
        }

        cartBox.addView(Ui.label(this, "Order type"));
        LinearLayout types = Ui.row(this);
        JSONObject ot = menu == null ? null : menu.optJSONObject("order_types");
        List<Button> tg = new ArrayList<>();
        if (ot != null) {
            Iterator<String> keys = ot.keys();
            while (keys.hasNext()) {
                String k = keys.next();
                Button b = Ui.chip(this, ot.optString(k), k.equals(cart.orderType));
                b.setOnClickListener(v -> {
                    cart.orderType = k;
                    for (Button o : tg) Ui.setChip(this, o, o == b);
                });
                tg.add(b);
                types.addView(b, Ui.wrapMargin(this, 8));
            }
        }
        cartBox.addView(types);

        LinearLayout sum = Ui.row(this);
        sum.setPadding(0, Ui.dp(this, 14), 0, Ui.dp(this, 6));
        sum.addView(Ui.text(this, "Subtotal", 17, false, Ui.TEXT), Ui.weight(1));
        sum.addView(Ui.text(this, "Tk " + Money.fmt(cart.subtotal()), 19, true, Ui.TEXT));
        cartBox.addView(sum);

        Button disc = Ui.outline(this, cart.hasDiscount()
                ? "Discount: " + ("percent".equals(cart.discountType) ? cart.discountValue + "%" : "Tk " + cart.discountValue) + " (change)"
                : "Add discount");
        disc.setOnClickListener(v -> openDiscount());
        cartBox.addView(disc, new LinearLayout.LayoutParams(-1, -2));

        Button pay = Ui.primary(this, "Pay");
        LinearLayout.LayoutParams pp = new LinearLayout.LayoutParams(-1, -2);
        pp.topMargin = Ui.dp(this, 12);
        pay.setOnClickListener(v -> openPay());
        cartBox.addView(pay, pp);

        Button clear = Ui.outline(this, "Clear cart");
        clear.setTextColor(Ui.RED);
        LinearLayout.LayoutParams cp = new LinearLayout.LayoutParams(-1, -2);
        cp.topMargin = Ui.dp(this, 24);
        clear.setOnClickListener(v -> new AlertDialog.Builder(this).setMessage("Remove everything from the cart?")
                .setNegativeButton("No", null).setPositiveButton("Clear", (d, w) -> { cart.clear(); showCart(false); }).show());
        cartBox.addView(clear, cp);
    }

    private void afterCartChange() {
        if (cart.isEmpty()) {
            cart.clear();
            showCart(false);
        } else {
            renderCart();
            updateCartBar();
        }
    }

    private void openDiscount() {
        LinearLayout box = Ui.column(this, 16);
        final String[] type = {cart.discountType == null ? "amount" : cart.discountType};
        LinearLayout row = Ui.row(this);
        Button tk = Ui.chip(this, "Tk", "amount".equals(type[0]));
        Button pc = Ui.chip(this, "%", "percent".equals(type[0]));
        tk.setOnClickListener(v -> { type[0] = "amount"; Ui.setChip(this, tk, true); Ui.setChip(this, pc, false); });
        pc.setOnClickListener(v -> { type[0] = "percent"; Ui.setChip(this, tk, false); Ui.setChip(this, pc, true); });
        row.addView(tk, Ui.wrapMargin(this, 8));
        row.addView(pc, Ui.wrapMargin(this, 8));
        box.addView(row);
        EditText value = Ui.money(this, "Amount or percent");
        if (cart.discountValue != null) value.setText(cart.discountValue);
        box.addView(value, new LinearLayout.LayoutParams(-1, -2));
        EditText reason = Ui.input(this, "Reason (needed)", InputType.TYPE_CLASS_TEXT);
        if (cart.discountReason != null) reason.setText(cart.discountReason);
        box.addView(reason, new LinearLayout.LayoutParams(-1, -2));
        box.addView(Ui.text(this, "Cashiers can give up to the limit set by the owner. The server checks it.", 13, false, Ui.MUTED));

        new AlertDialog.Builder(this).setTitle("Discount").setView(box)
                .setNeutralButton("Remove", (d, w) -> { cart.discountType = null; cart.discountValue = null; cart.discountReason = null; renderCart(); })
                .setNegativeButton("Cancel", null)
                .setPositiveButton("Save", (d, w) -> {
                    String v = value.getText().toString().trim();
                    if (Money.paisa(v) <= 0) {
                        cart.discountType = null;
                        cart.discountValue = null;
                    } else {
                        cart.discountType = type[0];
                        cart.discountValue = v;
                        cart.discountReason = reason.getText().toString().trim();
                    }
                    renderCart();
                }).show();
    }

    // ------------------------------------------------------------------- pay

    private void openPay() {
        if (cart.isEmpty() || menu == null) return;
        LinearLayout box = Ui.column(this, 16);
        TextView total = Ui.text(this, "Checking total…", 26, true, Ui.GREEN);
        total.setGravity(Gravity.CENTER);
        box.addView(total);
        TextView breakdown = Ui.text(this, "", 14, false, Ui.MUTED);
        breakdown.setGravity(Gravity.CENTER);
        box.addView(breakdown);
        TextView warn = Ui.text(this, "", 14, false, Ui.AMBER);
        box.addView(warn);

        box.addView(Ui.label(this, "Paid by"));
        JSONArray methods = menu.optJSONArray("payment_methods");
        final JSONObject[] method = {methods != null && methods.length() > 0 ? methods.optJSONObject(0) : null};
        LinearLayout mrow = flow(box);
        List<Button> mg = new ArrayList<>();
        LinearLayout cashBox = Ui.column(this, 0);
        LinearLayout refBox = Ui.column(this, 0);
        for (int i = 0; methods != null && i < methods.length(); i++) {
            JSONObject mt = methods.optJSONObject(i);
            Button b = Ui.chip(this, mt.optString("name"), i == 0);
            b.setOnClickListener(v -> {
                method[0] = mt;
                for (Button o : mg) Ui.setChip(this, o, o == b);
                cashBox.setVisibility(mt.optBoolean("is_cash") ? View.VISIBLE : View.GONE);
                refBox.setVisibility(mt.optBoolean("requires_reference") ? View.VISIBLE : View.GONE);
            });
            mg.add(b);
            mrow = addWrapped(box, mrow, b);
        }

        cashBox.addView(Ui.label(this, "Cash received (empty = exact)"));
        EditText tendered = Ui.money(this, "0.00");
        cashBox.addView(tendered, new LinearLayout.LayoutParams(-1, -2));
        LinearLayout quick = Ui.row(this);
        for (String q : new String[]{"100", "200", "500", "1000"}) {
            Button b = Ui.outline(this, q);
            b.setOnClickListener(v -> tendered.setText(q));
            quick.addView(b, Ui.weight(1));
        }
        cashBox.addView(quick);
        TextView change = Ui.text(this, "", 18, true, Ui.TEXT);
        cashBox.addView(change);
        box.addView(cashBox);

        refBox.addView(Ui.label(this, "Transaction ID"));
        EditText ref = Ui.input(this, "e.g. 9A7B3C", InputType.TYPE_CLASS_TEXT);
        refBox.addView(ref, new LinearLayout.LayoutParams(-1, -2));
        box.addView(refBox);

        if (method[0] != null) {
            cashBox.setVisibility(method[0].optBoolean("is_cash") ? View.VISIBLE : View.GONE);
            refBox.setVisibility(method[0].optBoolean("requires_reference") ? View.VISIBLE : View.GONE);
        }

        final JSONObject[] quote = {null};
        Runnable showChange = () -> {
            if (quote[0] == null) return;
            long due = Money.paisa(quote[0].optString("grand_total"));
            long t = Money.paisa(tendered.getText().toString());
            change.setText(t > 0 ? (t >= due ? "Change: Tk " + Money.fmt(t - due) : "Not enough cash") : "");
            change.setTextColor(t > 0 && t < due ? Ui.RED : Ui.TEXT);
        };
        tendered.addTextChangedListener(new android.text.TextWatcher() {
            public void beforeTextChanged(CharSequence s, int a, int b, int c) {}
            public void onTextChanged(CharSequence s, int a, int b, int c) {}
            public void afterTextChanged(android.text.Editable s) { showChange.run(); }
        });

        ScrollView sv = new ScrollView(this);
        sv.addView(box);
        AlertDialog dialog = new AlertDialog.Builder(this).setTitle("Payment").setView(sv)
                .setNegativeButton("Back", null)
                .setPositiveButton("Confirm & print", null)
                .create();
        dialog.setOnShowListener(d -> {
            Button ok = dialog.getButton(AlertDialog.BUTTON_POSITIVE);
            ok.setEnabled(false);
            ok.setOnClickListener(v -> {
                if (method[0] == null) return;
                long due = Money.paisa(quote[0].optString("grand_total"));
                String t = tendered.getText().toString().trim();
                if (method[0].optBoolean("is_cash") && !t.isEmpty() && Money.paisa(t) < due) {
                    warn.setText("Cash received is less than the bill.");
                    return;
                }
                if (method[0].optBoolean("requires_reference") && ref.getText().toString().trim().isEmpty()) {
                    warn.setText(method[0].optString("name") + " needs the transaction ID.");
                    return;
                }
                ok.setEnabled(false);
                placeOrder(dialog, warn, ok, method[0], method[0].optBoolean("is_cash") ? t : "", ref.getText().toString().trim(), quote[0]);
            });
            fetchQuote(quote, total, breakdown, warn, ok, showChange);
        });
        dialog.show();
    }

    private void fetchQuote(JSONObject[] quote, TextView total, TextView breakdown, TextView warn, Button ok, Runnable after) {
        io.execute(() -> {
            JSONObject q = null;
            String err = null;
            boolean off = false;
            try {
                q = api.post("/orders/quote", cart.payload());
            } catch (IOException e) {
                off = true;
            } catch (Api.ApiError e) {
                err = e.getMessage();
                if (e.status == 401) runOnUiThread(() -> handleApiError(e));
            } catch (JSONException e) {
                err = e.getMessage();
            }
            if (off) {
                try {
                    q = cart.localQuote(menu.optString("vat_rate", "0"), menu.optBoolean("vat_inclusive"));
                } catch (JSONException e) {
                    err = e.getMessage();
                }
            }
            final JSONObject fq = q;
            final String ferr = err;
            final boolean foff = off;
            runOnUiThread(() -> {
                if (fq == null) {
                    total.setText("—");
                    warn.setTextColor(Ui.RED);
                    warn.setText(ferr);
                    return;
                }
                quote[0] = fq;
                offline = foff;
                updateStatus();
                total.setText("Tk " + Money.fmt(Money.paisa(fq.optString("grand_total"))));
                StringBuilder b = new StringBuilder("Subtotal " + Money.fmt(Money.paisa(fq.optString("subtotal"))));
                if (Money.paisa(fq.optString("discount_total")) > 0) b.append("  ·  Discount −").append(Money.fmt(Money.paisa(fq.optString("discount_total"))));
                if (Money.paisa(fq.optString("tax_total")) > 0) b.append("  ·  VAT ").append(Money.fmt(Money.paisa(fq.optString("tax_total"))));
                breakdown.setText(b.toString());
                warn.setTextColor(Ui.AMBER);
                warn.setText(foff ? "Offline: the order is saved on this device and sent when the internet is back." : "");
                ok.setEnabled(true);
                after.run();
            });
        });
    }

    private void placeOrder(AlertDialog dialog, TextView warn, Button ok, JSONObject method, String tendered, String reference, JSONObject quote) {
        final JSONObject body;
        try {
            body = cart.payload();
            body.put("uuid", UUID.randomUUID().toString());
            JSONArray pays = new JSONArray();
            JSONObject p = new JSONObject();
            p.put("method", method.optString("code"));
            if (!tendered.isEmpty()) p.put("tendered", tendered);
            if (!reference.isEmpty()) p.put("reference", reference);
            pays.put(p);
            body.put("payments", pays);
        } catch (JSONException e) {
            warn.setText(e.getMessage());
            ok.setEnabled(true);
            return;
        }
        final List<Cart.Line> snapshot = new ArrayList<>(cart.lines);
        io.execute(() -> {
            try {
                JSONObject res = api.post("/orders", body);
                String printErr = Printer.print(res.optJSONArray("receipt"));
                runOnUiThread(() -> {
                    dialog.dismiss();
                    done(res.optString("order_number"), Money.paisa(res.optString("change_total")), printErr, res.optJSONArray("receipt"));
                });
            } catch (IOException e) {
                // No internet: keep it with the same uuid and the time it was taken.
                try {
                    body.put("created_offline_at", Cart.utcNow());
                    JSONObject entry = new JSONObject();
                    entry.put("payload", body);
                    entry.put("total", quote.optString("grand_total"));
                    entry.put("taken_at", System.currentTimeMillis());
                    prefs.enqueue(entry);
                    JSONArray lines = Receipts.offline(menu, snapshot, cart, quote, method.optString("name"), tendered);
                    String printErr = Printer.print(lines);
                    long change = tendered.isEmpty() ? 0 : Math.max(0, Money.paisa(tendered) - Money.paisa(quote.optString("grand_total")));
                    runOnUiThread(() -> {
                        offline = true;
                        dialog.dismiss();
                        done(null, change, printErr, lines);
                    });
                } catch (JSONException je) {
                    runOnUiThread(() -> { warn.setText("Could not save offline: " + je.getMessage()); ok.setEnabled(true); });
                }
            } catch (Api.ApiError e) {
                runOnUiThread(() -> {
                    warn.setTextColor(Ui.RED);
                    warn.setText(e.getMessage());
                    ok.setEnabled(true);
                    if (e.status == 401) { dialog.dismiss(); handleApiError(e); }
                });
            }
        });
    }

    private void done(String orderNumber, long change, String printErr, JSONArray receipt) {
        cart.clear();
        showCart(false);
        updateStatus();
        StringBuilder msg = new StringBuilder(orderNumber != null ? "Order " + orderNumber + " saved." : "No internet: order saved on this device. It gets its number when sent.");
        if (change > 0) msg.append("\n\nCHANGE: Tk ").append(Money.fmt(change));
        if (printErr != null) msg.append("\n\n").append(printErr);
        AlertDialog.Builder b = new AlertDialog.Builder(this).setTitle(orderNumber != null ? orderNumber : "Saved offline").setMessage(msg.toString())
                .setPositiveButton("New order", null);
        if (receipt != null) b.setNeutralButton("Print again", (d, w) -> printAsync(receipt));
        b.show();
    }

    void printAsync(JSONArray lines) {
        io.execute(() -> {
            String err = Printer.print(lines);
            if (err != null) runOnUiThread(() -> toast(err));
        });
    }

    // ------------------------------------------------------------- offline

    private void sync(boolean manual) {
        if (syncing || signedOut) return;
        final JSONArray q = prefs.queue();
        if (q.length() == 0) {
            if (manual) toast("Nothing to send");
            return;
        }
        syncing = true;
        updateStatus();
        io.execute(() -> {
            int sent = 0;
            boolean stopped = false;
            Api.ApiError authError = null;
            for (int i = 0; i < q.length(); i++) {
                JSONObject entry = q.optJSONObject(i);
                if (entry == null || (!manual && entry.has("error") && !entry.isNull("error"))) continue;
                try {
                    api.post("/orders", entry.getJSONObject("payload"));
                    removeFromQueue(entry.getJSONObject("payload").optString("uuid"));
                    sent++;
                } catch (IOException e) {
                    stopped = true;
                    break;
                } catch (Api.ApiError e) {
                    if (e.status == 401) { authError = e; break; }
                    if (e.status == 429 || e.status >= 500) { stopped = true; break; }
                    markError(entry.optJSONObject("payload").optString("uuid"), e.getMessage());
                } catch (JSONException e) {
                    markError(entry.optJSONObject("payload") == null ? "" : entry.optJSONObject("payload").optString("uuid"), e.getMessage());
                }
            }
            final int fsent = sent;
            final boolean foff = stopped;
            final Api.ApiError fauth = authError;
            runOnUiThread(() -> {
                syncing = false;
                offline = foff;
                updateStatus();
                if (fsent > 0) toast(fsent + " offline order(s) sent");
                if (fauth != null) handleApiError(fauth);
            });
        });
    }

    private void removeFromQueue(String uuid) {
        synchronized (prefs) {
            JSONArray q = prefs.queue();
            JSONArray keep = new JSONArray();
            for (int i = 0; i < q.length(); i++) {
                JSONObject e = q.optJSONObject(i);
                if (e != null && !uuid.equals(e.optJSONObject("payload").optString("uuid"))) keep.put(e);
            }
            prefs.saveQueue(keep);
        }
    }

    private void markError(String uuid, String error) {
        synchronized (prefs) {
            JSONArray q = prefs.queue();
            for (int i = 0; i < q.length(); i++) {
                JSONObject e = q.optJSONObject(i);
                if (e != null && uuid.equals(e.optJSONObject("payload").optString("uuid"))) {
                    try { e.put("error", error); } catch (JSONException ignored) { }
                }
            }
            prefs.saveQueue(q);
        }
    }

    private void showOfflineOrders() {
        JSONArray q = prefs.queue();
        if (q.length() == 0) {
            toast(offline ? "No internet. New orders will be kept on this device." : "All orders are sent");
            return;
        }
        LinearLayout box = Ui.column(this, 16);
        for (int i = 0; i < q.length(); i++) {
            JSONObject e = q.optJSONObject(i);
            String when = android.text.format.DateFormat.format("dd MMM hh:mm a", e.optLong("taken_at")).toString();
            box.addView(Ui.text(this, when + " · Tk " + Money.fmt(Money.paisa(e.optString("total"))), 16, true, Ui.TEXT));
            String err = e.isNull("error") ? null : e.optString("error", null);
            box.addView(Ui.text(this, err == null ? "Waiting to send" : "Not accepted: " + err, 14, false, err == null ? Ui.MUTED : Ui.RED));
            if (err != null) {
                final String uuid = e.optJSONObject("payload").optString("uuid");
                Button del = Ui.outline(this, "Delete this order");
                del.setTextColor(Ui.RED);
                del.setOnClickListener(v -> new AlertDialog.Builder(this).setMessage("Delete this offline order? It will not be in the sales report.")
                        .setNegativeButton("Keep", null).setPositiveButton("Delete", (d, w) -> { removeFromQueue(uuid); updateStatus(); toast("Deleted"); }).show());
                box.addView(del, new LinearLayout.LayoutParams(-1, -2));
            }
            box.addView(Ui.divider(this));
        }
        ScrollView sv = new ScrollView(this);
        sv.addView(box);
        new AlertDialog.Builder(this).setTitle("Offline orders (" + q.length() + ")").setView(sv)
                .setNegativeButton("Close", null)
                .setPositiveButton("Send now", (d, w) -> sync(true)).show();
    }

    private void updateStatus() {
        if (statusPill == null) return;
        int waiting = prefs.queue().length();
        if (syncing) {
            statusPill.setText("Sending " + waiting + " offline order(s)…");
            statusPill.setBackgroundColor(Ui.AMBER);
        } else if (offline) {
            statusPill.setText(waiting > 0 ? "Offline · " + waiting + " to send" : "Offline · orders are kept on this device");
            statusPill.setBackgroundColor(Ui.AMBER);
        } else if (waiting > 0) {
            statusPill.setText(waiting + " offline order(s) to send · tap");
            statusPill.setBackgroundColor(Ui.AMBER);
        } else {
            statusPill.setText(Printer.ready() ? "Online · printer ready" : "Online");
            statusPill.setBackgroundColor(Ui.GREEN);
        }
        invalidateOptionsMenu();
    }

    // ------------------------------------------------------------ app menu

    @Override
    public boolean onCreateOptionsMenu(Menu m) {
        m.add(0, MENU_ORDERS, 0, "Today's orders / reprint");
        m.add(0, MENU_OFFLINE, 1, "Offline orders (" + (prefs == null ? 0 : prefs.queue().length()) + ")");
        m.add(0, MENU_REFRESH, 2, "Refresh menu");
        m.add(0, MENU_TEST, 3, "Test print");
        m.add(0, MENU_SIGNOUT, 4, "Sign out");
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        switch (item.getItemId()) {
            case MENU_ORDERS:
                startActivity(new Intent(this, OrdersActivity.class));
                return true;
            case MENU_OFFLINE:
                showOfflineOrders();
                return true;
            case MENU_REFRESH:
                loadMenu(true);
                return true;
            case MENU_TEST:
                printAsync(Receipts.test(menu));
                return true;
            case MENU_SIGNOUT:
                signOut();
                return true;
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    private void signOut() {
        int waiting = prefs.queue().length();
        String msg = waiting > 0 ? waiting + " offline order(s) are not sent yet. They stay on this device and are sent after the next sign-in. Sign out?" : "Sign out?";
        new AlertDialog.Builder(this).setMessage(msg).setNegativeButton("Cancel", null).setPositiveButton("Sign out", (d, w) -> {
            final Api old = api;
            io.execute(() -> {
                try { old.post("/logout", new JSONObject()); } catch (Exception ignored) { }
            });
            prefs.signOut();
            toLogin();
        }).show();
    }

    private void handleApiError(Api.ApiError e) {
        if (e.status == 401) {
            if (signedOut) return;
            signedOut = true;
            prefs.signOut();
            toast("Signed out. Please sign in again.");
            toLogin();
        } else {
            toast(e.getMessage());
        }
    }

    private void toLogin() {
        startActivity(new Intent(this, LoginActivity.class));
        finish();
    }

    private void toast(String s) {
        Toast.makeText(this, s, Toast.LENGTH_LONG).show();
    }
}
