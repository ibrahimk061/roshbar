<?php

namespace App\Support;

/** Words printed on receipts, in config('pos.receipt_language'). */
final class ReceiptText
{
    public static function get(string $key): string
    {
        $lang = config('pos.receipt_language', 'bn');

        return (string) (config("pos.receipt_labels.$lang.$key") ?? config("pos.receipt_labels.en.$key") ?? $key);
    }

    public static function orderType(string $type): string
    {
        $lang = config('pos.receipt_language', 'bn');

        return (string) (config("pos.receipt_labels.$lang.order_types.$type") ?? config("pos.order_types.$type") ?? $type);
    }

    public static function ice(?string $ice): string
    {
        $lang = config('pos.receipt_language', 'bn');

        return (string) (config("pos.receipt_labels.$lang.ice_levels.$ice") ?? config("pos.ice_levels.$ice") ?? $ice);
    }
}
