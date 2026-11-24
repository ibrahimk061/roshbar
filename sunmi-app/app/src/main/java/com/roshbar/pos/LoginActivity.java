package com.roshbar.pos;

import android.app.Activity;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.text.InputType;
import android.view.Gravity;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;

import org.json.JSONObject;

import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/** Staff sign-in. A saved token skips straight to the POS. */
public class LoginActivity extends Activity {
    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private Prefs prefs;
    private EditText server, email, password;
    private TextView error;
    private Button go;

    @Override
    protected void onCreate(Bundle state) {
        super.onCreate(state);
        prefs = new Prefs(this);
        Printer.bind(this);
        if (prefs.token() != null) {
            openPos();
            return;
        }
        setTitle("Roshbar POS · Sign in");

        LinearLayout box = Ui.column(this, 24);
        box.setGravity(Gravity.CENTER_HORIZONTAL);
        TextView brand = Ui.text(this, "Roshbar", 34, true, Ui.GREEN);
        brand.setGravity(Gravity.CENTER);
        box.addView(brand);
        TextView sub = Ui.text(this, "Staff sign-in", 16, false, Ui.MUTED);
        sub.setGravity(Gravity.CENTER);
        sub.setPadding(0, 0, 0, Ui.dp(this, 16));
        box.addView(sub);

        box.addView(Ui.label(this, "Email"));
        email = Ui.input(this, "you@roshbar.com", InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS);
        box.addView(email, full());
        box.addView(Ui.label(this, "Password"));
        password = Ui.input(this, "Password", InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD);
        box.addView(password, full());
        box.addView(Ui.label(this, "Server"));
        server = Ui.input(this, "https://roshbar.com", InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_URI);
        server.setText(prefs.server());
        box.addView(server, full());

        error = Ui.text(this, "", 15, false, Ui.RED);
        error.setPadding(0, Ui.dp(this, 10), 0, Ui.dp(this, 10));
        box.addView(error, full());

        go = Ui.primary(this, "Sign in");
        go.setOnClickListener(v -> signIn());
        box.addView(go, full());

        ScrollView scroll = new ScrollView(this);
        scroll.addView(box);
        setContentView(scroll);
    }

    private LinearLayout.LayoutParams full() {
        return new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
    }

    private void signIn() {
        final String srv = Api.trimSlash(server.getText().toString());
        final String mail = email.getText().toString().trim();
        final String pass = password.getText().toString();
        if (!srv.startsWith("https://")) {
            error.setText("Server must start with https://");
            return;
        }
        if (mail.isEmpty() || pass.isEmpty()) {
            error.setText("Type your email and password.");
            return;
        }
        go.setEnabled(false);
        error.setText("");
        io.execute(() -> {
            String err = null;
            try {
                JSONObject body = new JSONObject();
                body.put("email", mail);
                body.put("password", pass);
                body.put("device_name", (Build.MANUFACTURER + " " + Build.MODEL).trim());
                JSONObject res = new Api(srv, null).post("/login", body);
                prefs.signIn(srv, res.getString("token"), res.getJSONObject("user").optString("name"));
            } catch (IOException e) {
                err = "Cannot reach the server. Check the internet.";
            } catch (Api.ApiError e) {
                err = e.getMessage();
            } catch (Exception e) {
                err = "Sign-in failed: " + e.getMessage();
            }
            final String msg = err;
            runOnUiThread(() -> {
                go.setEnabled(true);
                if (msg != null) {
                    error.setText(msg);
                } else {
                    openPos();
                }
            });
        });
    }

    private void openPos() {
        startActivity(new Intent(this, PosActivity.class));
        finish();
    }

    @Override
    protected void onDestroy() {
        io.shutdownNow();
        super.onDestroy();
    }
}
