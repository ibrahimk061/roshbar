<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Money;
use App\Support\ReceiptText as T;
use Illuminate\Support\Str;

/**
 * The receipt as ready-to-print text lines for a 58 mm thermal printer
 * (SUNMI V2s: 384 dots wide). Size 1 is the 24 px font, 32 characters a
 * line; size 2 is the 36 px font, 21 characters a line; size 3 is extra big (order number). The app only
 * prints these lines, so the layout stays the same as the web receipt and
 * changes here reach every device without an app update.
 *
 * Each line: ['text' => string, 'align' => left|center|right, 'bold' => bool, 'size' => 1|2]
 * or ['divider' => true]. Two-sided lines also carry 'left' and 'right'.
 * Words come from config pos.receipt_labels (Bangla by default); the SUNMI app
 * draws the lines as an image, so Bangla and ৳ print correctly.
 */
class ReceiptLines
{
    public const WIDTH = 32;

    public const BIG_WIDTH = 21;

    private int $width = self::WIDTH;

    public function for(Order $order): array
    {
        $order->loadMissing(['items.addons', 'payments', 'user']);
        $local = $order->localCreatedAt();
        $w = $this->width;
        $out = [];

        foreach ($this->wrap(Setting::get('business_name', config('pos.business_name')), self::BIG_WIDTH) as $t) {
            $out[] = $this->line($t, 'center', true, 2);
        }
        if ($a = Setting::get('shop_address')) {
            foreach ($this->wrap($a) as $t) {
                $out[] = $this->line($t, 'center');
            }
        }
        if ($p = Setting::get('shop_phone')) {
            $out[] = $this->line(T::get('phone').': '.$p, 'center');
        }
        $out[] = ['divider' => true];
        [$prefix, $seq] = T::splitNumber($order->order_number);
        if ($prefix !== '') {
            $out[] = $this->line($prefix, 'center', true);
        }
        $out[] = $this->line($seq, 'center', true, 3); // size 3: extra big, for calling the order
        $out[] = $this->two($local->format('d-m-Y'), $local->format('h:i A'));
        $out[] = $this->two(T::get('type'), T::orderType($order->order_type));
        if ($order->user) {
            $out[] = $this->two(T::get('served_by'), $order->user->name);
        }
        $out[] = ['divider' => true];

        foreach ($order->items as $item) {
            foreach ($this->wrap($item->product_name.' ('.$item->variant_name.')') as $t) {
                $out[] = $this->line($t, 'left', true);
            }
            $unit = Money::of($item->unit_price)->plus(Money::of($item->addons_unit_total));
            $out[] = $this->two(' '.$item->quantity.' x '.Money::format($unit), Money::format($item->line_total));
            $meta = [];
            if ($item->sugar !== null) {
                $meta[] = T::get('sugar').' '.$item->sugar.'%';
            }
            if ($item->ice) {
                $meta[] = T::ice($item->ice);
            }
            if ($meta) {
                $out[] = $this->line('  '.implode(' | ', $meta));
            }
            foreach ($item->addons as $ad) {
                $out[] = $this->line(Str::limit('  + '.$ad->addon_name.' ('.Money::format($ad->price).')', $w, ''));
            }
            if ($item->note) {
                foreach ($this->wrap('  * '.$item->note) as $t) {
                    $out[] = $this->line($t);
                }
            }
        }

        $out[] = ['divider' => true];
        $out[] = $this->two(T::get('subtotal'), Money::format($order->subtotal));
        if (Money::of($order->discount_total)->isPositive()) {
            $label = T::get('discount').($order->discount_type === 'percent' ? ' '.$this->trim($order->discount_value).'%' : '');
            $out[] = $this->two($label, '-'.Money::format($order->discount_total));
        }
        if (Money::of($order->tax_total)->isPositive()) {
            $label = T::get('vat').' '.$this->trim($order->tax_rate).'%'.($order->tax_inclusive ? ' ('.T::get('incl').')' : '');
            $out[] = $this->two($label, Money::format($order->tax_total));
        }
        $out[] = ['divider' => true];
        $out[] = $this->two(T::get('total'), T::get('currency').' '.Money::format($order->grand_total), true, 2);
        $out[] = ['divider' => true];

        foreach ($order->payments as $pay) {
            $out[] = $this->two($pay->method_name.($pay->reference ? ' #'.$pay->reference : ''), Money::format($pay->amount));
            if ($pay->tendered !== null) {
                $out[] = $this->two(' '.T::get('cash_received'), Money::format($pay->tendered));
            }
        }
        if (Money::of($order->change_total)->isPositive()) {
            $out[] = $this->two(T::get('change'), Money::format($order->change_total), true);
        }
        $due = Money::of($order->grand_total)->minus(Money::of($order->paid_total));
        if ($order->status === 'cancelled') {
            $out[] = $this->line(T::get('cancelled'), 'center', true);
        } elseif ($due->isPositive()) {
            $out[] = $this->two(T::get('due'), Money::format($due), true);
        } else {
            $out[] = $this->line(T::get('paid'), 'center', true);
        }
        $out[] = ['divider' => true];
        if ($f = Setting::get('receipt_footer', 'Thank you! Come again.')) {
            foreach ($this->wrap($f) as $t) {
                $out[] = $this->line($t, 'center');
            }
        }
        $out[] = $this->line(T::get('items').': '.$order->items->sum('quantity'), 'center');

        return $out;
    }

    /** Left and right text on one line: 'text' is padded for a plain-text printer, 'left'/'right' let the app draw them apart. */
    private function two(string $left, string $right, bool $bold = false, int $size = 1): array
    {
        $width = $size === 2 ? self::BIG_WIDTH : self::WIDTH;

        return $this->line($this->pair($left, $right, $width), 'left', $bold, $size) + ['left' => $left, 'right' => $right];
    }

    private function line(string $text, string $align = 'left', bool $bold = false, int $size = 1): array
    {
        return ['text' => $text, 'align' => $align, 'bold' => $bold, 'size' => $size];
    }

    /** Left text and right text on one line; the left side is cut if both do not fit. */
    private function pair(string $left, string $right, ?int $width = null): string
    {
        $width ??= $this->width;
        $room = max(1, $width - mb_strlen($right) - 1);
        $left = mb_strlen($left) > $room ? mb_substr($left, 0, $room) : $left;

        return $left.str_repeat(' ', max(1, $width - mb_strlen($left) - mb_strlen($right))).$right;
    }

    private function wrap(string $text, ?int $width = null): array
    {
        return explode("\n", wordwrap($text, $width ?? $this->width, "\n", true));
    }

    private function trim($n): string
    {
        return rtrim(rtrim((string) $n, '0'), '.');
    }
}
