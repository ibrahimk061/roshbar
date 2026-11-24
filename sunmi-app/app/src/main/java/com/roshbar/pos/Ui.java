package com.roshbar.pos;

import android.content.Context;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.text.InputType;
import android.util.TypedValue;
import android.view.Gravity;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;

/** Small helpers to build screens in code (white + green brand). */
final class Ui {
    static final int GREEN = Color.parseColor("#16834A");
    static final int GREEN_SOFT = Color.parseColor("#E8F5EE");
    static final int TEXT = Color.parseColor("#1B2620");
    static final int MUTED = Color.parseColor("#66756C");
    static final int BORDER = Color.parseColor("#DDE6E0");
    static final int RED = Color.parseColor("#C0392B");
    static final int AMBER = Color.parseColor("#B26A00");

    private Ui() {}

    static int dp(Context c, float v) {
        return (int) TypedValue.applyDimension(TypedValue.COMPLEX_UNIT_DIP, v, c.getResources().getDisplayMetrics());
    }

    static LinearLayout column(Context c, int padDp) {
        LinearLayout l = new LinearLayout(c);
        l.setOrientation(LinearLayout.VERTICAL);
        int p = dp(c, padDp);
        l.setPadding(p, p, p, p);
        return l;
    }

    static LinearLayout row(Context c) {
        LinearLayout l = new LinearLayout(c);
        l.setOrientation(LinearLayout.HORIZONTAL);
        l.setGravity(Gravity.CENTER_VERTICAL);
        return l;
    }

    static TextView text(Context c, String s, float sp, boolean bold, int color) {
        TextView t = new TextView(c);
        t.setText(s);
        t.setTextSize(TypedValue.COMPLEX_UNIT_SP, sp);
        t.setTextColor(color);
        if (bold) t.setTypeface(Typeface.DEFAULT_BOLD);
        return t;
    }

    static TextView label(Context c, String s) {
        TextView t = text(c, s, 14, true, MUTED);
        t.setPadding(0, dp(c, 12), 0, dp(c, 4));
        return t;
    }

    static Button primary(Context c, String s) {
        Button b = new Button(c);
        b.setText(s);
        b.setAllCaps(false);
        b.setTextSize(TypedValue.COMPLEX_UNIT_SP, 18);
        b.setTypeface(Typeface.DEFAULT_BOLD);
        b.setTextColor(Color.WHITE);
        b.setBackground(round(c, GREEN, GREEN, 10));
        b.setMinHeight(dp(c, 52));
        return b;
    }

    static Button outline(Context c, String s) {
        Button b = new Button(c);
        b.setText(s);
        b.setAllCaps(false);
        b.setTextSize(TypedValue.COMPLEX_UNIT_SP, 16);
        b.setTextColor(GREEN);
        b.setBackground(round(c, Color.WHITE, GREEN, 10));
        b.setMinHeight(dp(c, 46));
        return b;
    }

    /** A toggle chip; selected chips are filled green. */
    static Button chip(Context c, String s, boolean selected) {
        Button b = new Button(c);
        b.setText(s);
        b.setAllCaps(false);
        b.setTextSize(TypedValue.COMPLEX_UNIT_SP, 15);
        b.setMinHeight(dp(c, 44));
        b.setMinWidth(dp(c, 56));
        b.setPadding(dp(c, 12), 0, dp(c, 12), 0);
        setChip(c, b, selected);
        return b;
    }

    static void setChip(Context c, Button b, boolean selected) {
        b.setTextColor(selected ? Color.WHITE : GREEN);
        b.setBackground(round(c, selected ? GREEN : Color.WHITE, GREEN, 22));
    }

    static GradientDrawable round(Context c, int fill, int stroke, int radiusDp) {
        GradientDrawable d = new GradientDrawable();
        d.setColor(fill);
        d.setCornerRadius(dp(c, radiusDp));
        d.setStroke(dp(c, 1), stroke);
        return d;
    }

    static EditText input(Context c, String hint, int type) {
        EditText e = new EditText(c);
        e.setHint(hint);
        e.setInputType(type);
        e.setSingleLine(true);
        e.setTextSize(TypedValue.COMPLEX_UNIT_SP, 18);
        return e;
    }

    static EditText money(Context c, String hint) {
        return input(c, hint, InputType.TYPE_CLASS_NUMBER | InputType.TYPE_NUMBER_FLAG_DECIMAL);
    }

    static LinearLayout.LayoutParams weight(float w) {
        return new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, w);
    }

    static LinearLayout.LayoutParams wrapMargin(Context c, int rightDp) {
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        p.rightMargin = dp(c, rightDp);
        p.bottomMargin = dp(c, 6);
        return p;
    }

    static View divider(Context c) {
        View v = new View(c);
        v.setBackgroundColor(BORDER);
        v.setLayoutParams(new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(c, 1)));
        return v;
    }
}
