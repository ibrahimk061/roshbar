package com.roshbar.pos;

/** Money as whole paisa (long), the same way the server and web POS count it. Never float. */
final class Money {
    private Money() {}

    /** "123.4" / "123.45" / "123" -> 12345 paisa. Bad input -> 0. */
    static long paisa(String s) {
        if (s == null) return 0;
        s = s.trim();
        if (s.isEmpty()) return 0;
        boolean neg = s.startsWith("-");
        if (neg) s = s.substring(1);
        String[] parts = s.split("\\.", -1);
        try {
            long taka = parts[0].isEmpty() ? 0 : Long.parseLong(parts[0]);
            long p = 0;
            if (parts.length > 1) {
                String f = (parts[1] + "00").substring(0, 2);
                p = Long.parseLong(f);
            }
            long v = taka * 100 + p;
            return neg ? -v : v;
        } catch (NumberFormatException e) {
            return 0;
        }
    }

    /** 12345 -> "123.45" (what the API expects). */
    static String str(long paisa) {
        long a = Math.abs(paisa);
        return (paisa < 0 ? "-" : "") + (a / 100) + "." + String.format(java.util.Locale.US, "%02d", a % 100);
    }

    /** 1234500 -> "12,345.00" (for the screen). */
    static String fmt(long paisa) {
        long a = Math.abs(paisa);
        String taka = String.format(java.util.Locale.US, "%,d", a / 100);
        return (paisa < 0 ? "-" : "") + taka + "." + String.format(java.util.Locale.US, "%02d", a % 100);
    }

    /** Round half up of n / d for non-negative numbers. */
    static long divRound(long n, long d) {
        return (n * 2 + d) / (d * 2);
    }
}
