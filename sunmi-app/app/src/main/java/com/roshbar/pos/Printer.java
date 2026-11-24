package com.roshbar.pos;

import android.content.Context;
import android.os.RemoteException;
import android.util.Log;

import com.sunmi.peripheral.printer.InnerPrinterCallback;
import com.sunmi.peripheral.printer.InnerPrinterException;
import com.sunmi.peripheral.printer.InnerPrinterManager;
import com.sunmi.peripheral.printer.SunmiPrinterService;
import com.sunmi.peripheral.printer.WoyouConsts;

import org.json.JSONArray;
import org.json.JSONObject;

/**
 * The SUNMI V2s built-in 58 mm printer. The server sends ready-made lines
 * (see docs/API.md); this class only prints them. On a phone without the
 * SUNMI printer service, ready() stays false and printing is skipped.
 */
final class Printer {
    private static final String TAG = "RoshbarPrinter";
    static final float FONT_NORMAL = 24f; // 32 characters a line
    static final float FONT_BIG = 36f;    // 21 characters a line
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
            s.enterPrinterBuffer(true);
            s.printerInit(null);
            for (int i = 0; i < lines.length(); i++) {
                JSONObject l = lines.optJSONObject(i);
                if (l == null) continue;
                if (l.optBoolean("divider")) {
                    s.setAlignment(0, null);
                    s.printTextWithFont(repeat('-', WIDTH) + "\n", "", FONT_NORMAL, null);
                    continue;
                }
                String align = l.optString("align", "left");
                s.setAlignment("center".equals(align) ? 1 : "right".equals(align) ? 2 : 0, null);
                s.setPrinterStyle(WoyouConsts.ENABLE_BOLD, l.optBoolean("bold") ? WoyouConsts.ENABLE : WoyouConsts.DISABLE);
                float size = l.optInt("size", 1) == 2 ? FONT_BIG : FONT_NORMAL;
                s.printTextWithFont(l.optString("text", "") + "\n", "", size, null);
            }
            s.setPrinterStyle(WoyouConsts.ENABLE_BOLD, WoyouConsts.DISABLE);
            s.lineWrap(4, null);
            s.exitPrinterBuffer(true);
            return null;
        } catch (RemoteException | RuntimeException e) {
            Log.e(TAG, "Print failed", e);
            return "Print failed: " + e.getMessage();
        }
    }

    static String repeat(char ch, int n) {
        StringBuilder b = new StringBuilder(n);
        for (int i = 0; i < n; i++) b.append(ch);
        return b.toString();
    }
}
