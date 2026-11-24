package com.roshbar.pos;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Typeface;
import android.os.RemoteException;
import android.util.Log;

import com.sunmi.peripheral.printer.InnerPrinterCallback;
import com.sunmi.peripheral.printer.InnerPrinterException;
import com.sunmi.peripheral.printer.InnerPrinterManager;
import com.sunmi.peripheral.printer.SunmiPrinterService;

import org.json.JSONArray;
import org.json.JSONObject;

/**
 * The SUNMI V2s built-in 58 mm printer. The server sends ready-made lines
 * (see docs/API.md); this class only prints them. On a phone without the
 * SUNMI printer service, ready() stays false and printing is skipped.
 */
final class Printer {
    private static final String TAG = "RoshbarPrinter";
    static final int WIDTH = 32;

    private static SunmiPrinterService service;
    private static boolean binding;

    private static final InnerPrinterCallback CALLBACK = new InnerPrinterCallback() {
        @Override
        protected void onConnected(SunmiPrinterService s) {
            service = s;
            binding = false;
        }

        @Override
        protected void onDisconnected() {
            service = null;
            binding = false;
        }
    };

    private Printer() {}

    static void bind(Context c) {
        if (service != null || binding) return;
        try {
            binding = InnerPrinterManager.getInstance().bindService(c.getApplicationContext(), CALLBACK);
        } catch (InnerPrinterException e) {
            Log.w(TAG, "No SUNMI printer service", e);
            binding = false;
        }
    }

    static boolean ready() {
        return service != null;
    }

    /** Returns null when printed, or a short reason to show the cashier. */
    static String print(JSONArray lines) {
        SunmiPrinterService s = service;
        if (s == null) return "Printer not found (is this a SUNMI device?)";
        if (lines == null || lines.length() == 0) return "Nothing to print";
        try {
            // SUNMI states: 1 ok, 2 starting, 3 error, 4 no paper, 5 too hot, 6 cover open, 505 no printer.
            switch (s.updatePrinterState()) {
                case 4: return "Printer: out of paper";
                case 5: return "Printer is too hot. Wait a minute.";
                case 6: return "Printer cover is open";
                case 505: return "This device has no printer";
                case 3: return "Printer error. Restart the device.";
                default: break;
            }
            // Drawn as one image: Android shapes Bangla text and has the ৳ sign,
            // which the printer's own text fonts do not.
            Bitmap img = render(lines);
            s.enterPrinterBuffer(true);
            s.printerInit(null);
            s.setAlignment(1, null);
            s.printBitmap(img, null);
            s.lineWrap(4, null);
            s.exitPrinterBuffer(true);
            img.recycle();
            return null;
        } catch (RemoteException | RuntimeException e) {
            Log.e(TAG, "Print failed", e);
            return "Print failed: " + e.getMessage();
        }
    }

    static final int DOTS = 384;           // 58 mm paper, 8 dots per mm
    private static final float PX_NORMAL = 24f;
    private static final float PX_BIG = 34f;
    private static final float PX_HUGE = 64f; // size 3: order number

    /** The receipt lines as a black-on-white image 384 dots wide. */
    static Bitmap render(JSONArray lines) {
        Paint p = new Paint(Paint.ANTI_ALIAS_FLAG);
        p.setColor(Color.BLACK);
        int gap = 6;
        int height = 8;
        for (int i = 0; i < lines.length(); i++) {
            JSONObject l = lines.optJSONObject(i);
            if (l == null) continue;
            if (l.optBoolean("divider")) { height += 14; continue; }
            p.setTextSize(px(l.optInt("size", 1)));
            Paint.FontMetrics fm = p.getFontMetrics();
            height += (int) Math.ceil(fm.descent - fm.ascent) + gap;
        }
        Bitmap b = Bitmap.createBitmap(DOTS, Math.max(height + 8, 16), Bitmap.Config.ARGB_8888);
        Canvas c = new Canvas(b);
        c.drawColor(Color.WHITE);
        float y = 8;
        for (int i = 0; i < lines.length(); i++) {
            JSONObject l = lines.optJSONObject(i);
            if (l == null) continue;
            if (l.optBoolean("divider")) {
                p.setStrokeWidth(2);
                for (int x = 0; x < DOTS; x += 10) c.drawLine(x, y + 7, x + 6, y + 7, p);
                y += 14;
                continue;
            }
            p.setTextSize(px(l.optInt("size", 1)));
            p.setTypeface(l.optBoolean("bold") ? Typeface.DEFAULT_BOLD : Typeface.DEFAULT);
            Paint.FontMetrics fm = p.getFontMetrics();
            float base = y - fm.ascent;
            if (l.has("left") || l.has("right")) {
                String right = l.optString("right", "");
                float rw = p.measureText(right);
                String left = fit(p, l.optString("left", ""), DOTS - rw - 12);
                c.drawText(left, 0, base, p);
                c.drawText(right, DOTS - rw, base, p);
            } else {
                String text = l.optString("text", "");
                while (p.measureText(text) > DOTS && p.getTextSize() > 14) p.setTextSize(p.getTextSize() - 1);
                text = fit(p, text, DOTS);
                float w = p.measureText(text);
                String align = l.optString("align", "left");
                float x = "center".equals(align) ? (DOTS - w) / 2 : "right".equals(align) ? DOTS - w : 0;
                c.drawText(text, x, base, p);
            }
            y += (fm.descent - fm.ascent) + gap;
        }
        return b;
    }

    private static float px(int size) {
        return size == 3 ? PX_HUGE : size == 2 ? PX_BIG : PX_NORMAL;
    }

    /** Cut the text so it fits the width. */
    private static String fit(Paint p, String s, float width) {
        if (width <= 0) return "";
        if (p.measureText(s) <= width) return s;
        int n = p.breakText(s, true, width, null);
        return s.substring(0, Math.max(0, n));
    }

    static String repeat(char ch, int n) {
        StringBuilder b = new StringBuilder(n);
        for (int i = 0; i < n; i++) b.append(ch);
        return b.toString();
    }
}
