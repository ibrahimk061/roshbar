package com.roshbar.pos;

import android.content.Context;
import android.content.SharedPreferences;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

/** Everything the app keeps on the device: server, sign-in, last menu, offline orders. */
final class Prefs {
    private static final String FILE = "roshbar";
    private final SharedPreferences sp;

    Prefs(Context c) {
        sp = c.getApplicationContext().getSharedPreferences(FILE, Context.MODE_PRIVATE);
    }

    String server() {
        return sp.getString("server", BuildConfig.DEFAULT_SERVER);
    }

    String token() {
        return sp.getString("token", null);
    }

    String userName() {
        return sp.getString("user_name", "");
    }

    void signIn(String server, String token, String userName) {
        sp.edit().putString("server", server).putString("token", token).putString("user_name", userName).apply();
    }

    /** Offline orders stay on the device after sign-out, so nothing is lost. */
    void signOut() {
        sp.edit().remove("token").remove("user_name").apply();
    }

    JSONObject menu() {
        String s = sp.getString("menu", null);
        if (s == null) return null;
        try {
            return new JSONObject(s);
        } catch (JSONException e) {
            return null;
        }
    }

    void saveMenu(JSONObject menu) {
        sp.edit().putString("menu", menu.toString()).apply();
    }

    /** Each entry: {"payload": {...order...}, "total": "640.00", "error": null|"reason"} */
    synchronized JSONArray queue() {
        try {
            return new JSONArray(sp.getString("queue", "[]"));
        } catch (JSONException e) {
            return new JSONArray();
        }
    }

    synchronized void saveQueue(JSONArray q) {
        // commit (not apply): an offline order must be on disk before we say "saved".
        sp.edit().putString("queue", q.toString()).commit();
    }

    synchronized void enqueue(JSONObject entry) {
        JSONArray q = queue();
        q.put(entry);
        saveQueue(q);
    }
}
