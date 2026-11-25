package com.roshbar.pos;

import android.app.Activity;
import android.app.AlertDialog;
import android.os.Bundle;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/** Today's orders on the server, to reprint a receipt. */
public class OrdersActivity extends Activity {
    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private Api api;
    private LinearLayout list;

    @Override
    protected void onCreate(Bundle state) {
        super.onCreate(state);
        Prefs prefs = new Prefs(this);
        if (prefs.token() == null) {
            finish();
            return;
        }
        api = new Api(prefs.server(), prefs.token());
        ScrollView sv = new ScrollView(this);
        list = Ui.column(this, 12);
        sv.addView(list);
        setContentView(sv);
        load();
    }

    private void load() {
        list.removeAllViews();
        list.addView(Ui.text(this, "Loading…", 16, false, Ui.MUTED));
        io.execute(() -> {
            try {
                JSONArray orders = api.get("/orders/today?limit=100").optJSONArray("orders");
                runOnUiThread(() -> render(orders));
            } catch (IOException e) {
                runOnUiThread(() -> message("No internet. Reprint needs the server."));
            } catch (Api.ApiError e) {
                runOnUiThread(() -> message(e.getMessage()));
            }
        });
    }

    private void message(String s) {
        list.removeAllViews();
        list.addView(Ui.text(this, s, 16, false, Ui.RED));
    }

    private void render(JSONArray orders) {
        list.removeAllViews();
        if (orders == null || orders.length() == 0) {
            list.addView(Ui.text(this, "No orders today yet.", 16, false, Ui.MUTED));
            return;
        }
        for (int i = 0; i < orders.length(); i++) {
            JSONObject o = orders.optJSONObject(i);
            LinearLayout row = Ui.row(this);
            row.setPadding(0, Ui.dp(this, 8), 0, Ui.dp(this, 8));
            LinearLayout info = Ui.column(this, 0);
            boolean cancelled = "cancelled".equals(o.optString("status"));
            info.addView(Ui.text(this, o.optString("order_number"), 16, true, cancelled ? Ui.MUTED : Ui.TEXT));
            String time = o.optString("created_at");
            if (time.length() >= 16) time = time.substring(11, 16);
            info.addView(Ui.text(this, time + " · Tk " + Money.fmt(Money.paisa(o.optString("grand_total"))) + (cancelled ? " · cancelled" : ""), 14, false, Ui.MUTED));
            row.addView(info, Ui.weight(1));
            Button print = Ui.outline(this, "Reprint");
            final long id = o.optLong("id");
            print.setOnClickListener(v -> reprint(id, o.optString("order_number")));
            row.addView(print, new LinearLayout.LayoutParams(-2, -2));
            list.addView(row);
            list.addView(Ui.divider(this));
        }
    }

    private void reprint(long id, String number) {
        // Load the receipt first and show it, so what you see is what prints.
        io.execute(() -> {
            JSONArray lines = null;
            String err = null;
            try {
                lines = api.get("/orders/" + id + "/receipt").optJSONArray("receipt");
            } catch (IOException e) {
                err = "No internet.";
            } catch (Api.ApiError e) {
                err = e.getMessage();
            }
            final JSONArray fl = lines;
            final String fe = err;
            runOnUiThread(() -> {
                if (fl == null) {
                    Toast.makeText(this, fe, Toast.LENGTH_LONG).show();
                    return;
                }
                new AlertDialog.Builder(this).setTitle(number).setView(Ui.receiptPreview(this, null, fl))
                        .setNegativeButton("Close", null)
                        .setPositiveButton("Print", (d, w) -> io.execute(() -> {
                            String pe = Printer.print(fl);
                            runOnUiThread(() -> Toast.makeText(this, pe == null ? "Printed" : pe, Toast.LENGTH_LONG).show());
                        })).show();
            });
        });
    }

    @Override
    protected void onDestroy() {
        io.shutdownNow();
        super.onDestroy();
    }
}
