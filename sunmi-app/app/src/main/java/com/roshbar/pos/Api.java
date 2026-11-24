package com.roshbar.pos;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.Iterator;

/** Small JSON client for /api/v1. Call from a background thread. */
final class Api {
    /** The server answered with an error (bad input, signed out, ...). */
    static final class ApiError extends Exception {
        final int status;

        ApiError(int status, String message) {
            super(message);
            this.status = status;
        }
    }

    private final String base;
    private final String token;

    Api(String server, String token) {
        this.base = trimSlash(server) + "/api/v1";
        this.token = token;
    }

    static String trimSlash(String s) {
        s = s.trim();
        while (s.endsWith("/")) s = s.substring(0, s.length() - 1);
        return s;
    }

    JSONObject get(String path) throws IOException, ApiError {
        return send("GET", path, null);
    }

    JSONObject post(String path, JSONObject body) throws IOException, ApiError {
        return send("POST", path, body);
    }

    /** IOException = no internet / server unreachable (the order can be kept offline). */
    private JSONObject send(String method, String path, JSONObject body) throws IOException, ApiError {
        HttpURLConnection c = (HttpURLConnection) new URL(base + path).openConnection();
        try {
            c.setRequestMethod(method);
            c.setConnectTimeout(8000);
            c.setReadTimeout(20000);
            c.setRequestProperty("Accept", "application/json");
            if (token != null) c.setRequestProperty("Authorization", "Bearer " + token);
            if (body != null) {
                byte[] bytes = body.toString().getBytes(StandardCharsets.UTF_8);
                c.setDoOutput(true);
                c.setRequestProperty("Content-Type", "application/json; charset=utf-8");
                c.setFixedLengthStreamingMode(bytes.length);
                try (OutputStream os = c.getOutputStream()) {
                    os.write(bytes);
                }
            }
            int status = c.getResponseCode();
            InputStream in = status >= 400 ? c.getErrorStream() : c.getInputStream();
            String text = in == null ? "" : read(in);
            JSONObject json;
            try {
                json = text.isEmpty() ? new JSONObject() : new JSONObject(text);
            } catch (JSONException e) {
                if (status >= 500 || status == 404) throw new ApiError(status, "Server error (" + status + "). Try again.");
                throw new ApiError(status, "Unexpected answer from the server (" + status + ").");
            }
            if (status >= 400) throw new ApiError(status, message(status, json));
            return json;
        } finally {
            c.disconnect();
        }
    }

    /** Laravel errors: {"message": "...", "errors": {"field": ["first message", ...]}} */
    private static String message(int status, JSONObject json) {
        JSONObject errors = json.optJSONObject("errors");
        if (errors != null) {
            Iterator<String> keys = errors.keys();
            if (keys.hasNext()) {
                JSONArray list = errors.optJSONArray(keys.next());
                if (list != null && list.length() > 0) return list.optString(0);
            }
        }
        if (status == 401) return "Signed out. Please sign in again.";
        if (status == 429) return "Too many tries. Wait a minute.";
        String m = json.optString("message", "");
        return m.isEmpty() ? "Error " + status : m;
    }

    private static String read(InputStream in) throws IOException {
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        byte[] buf = new byte[8192];
        int n;
        while ((n = in.read(buf)) > 0) out.write(buf, 0, n);
        in.close();
        return out.toString("UTF-8");
    }
}
