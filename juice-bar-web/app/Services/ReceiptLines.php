<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * The receipt as ready-to-print text lines for a 58 mm thermal printer
 * (SUNMI V2s: 384 dots wide). Size 1 is the 24 px font, 32 characters a
 * line; size 2 is the 36 px font, 21 characters a line. The app only
 * prints these lines, so the layout stays the same as the web receipt and
 * changes here reach every device without an app update.
 *
 * Each line: ['text' => string, 'align' => left|center|right, 'bold' => bool, 'size' => 1|2]
 * or ['divider' => true]. "Tk" is used instead of ৳ (printer fonts lack it).
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
            $out[] = $this->line('Phone: '.$p, 'center');
        }
        $out[] = ['divider' => true];
        $out[] = $this->line($order->order_number, 'center', true, 2);
        $out[] = $this->line($this->pair($local->format('d-m-Y'), $local->format('h:i A')));
        $out[] = $this->line($this->pair('Type', config('pos.order_types.'.$order->order_type, $order->order_type)));
        if ($order->user) {
            $out[] = $this->line($this->pair('Served by', $order->user->name));
        }
        $out[] = ['divider' => true];

        foreach ($order->items as $item) {
            foreach ($this->wrap($item->product_name.' ('.$item->variant_name.')') as $t) {
                $out[] = $this->line($t, 'left', true);
            }
            $unit = Money::of($item->unit_price)->plus(Money::of($item->addons_unit_total));
            $out[] = $this->line($this->pair(' '.$item->quantity.' x '.Money::format($unit), Money::format($item->line_total)));
            $meta = [];
            if ($item->sugar !== null) {
                $meta[] = 'Sugar '.$item->sugar.'%';
            }
            if ($item->ice) {
                $meta[] = config('pos.ice_levels.'.$item->ice, $item->ice);
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
        $out[] = $this->line($this->pair('Subtotal', Money::format($order->subtotal)));
        if (Money::of($order->discount_total)->isPositive()) {
            $label = 'Discount'.($order->discount_type === 'percent' ? ' '.$this->trim($order->discount_value).'%' : '');
            $out[] = $this->line($this->pair($label, '-'.Money::format($order->discount_total)));
        }
        if (Money::of($order->tax_total)->isPositive()) {
            $label = 'VAT '.$this->trim($order->tax_rate).'%'.($order->tax_inclusive ? ' (incl.)' : '');
            $out[] = $this->line($this->pair($label, Money::format($order->tax_total)));
        }
        $out[] = ['divider' => true];
        $out[] = $this->line($this->pair('TOTAL', 'Tk '.Money::format($order->grand_total), self::BIG_WIDTH), 'left', true, 2);
        $out[] = ['divider' => true];

        foreach ($order->payments as $pay) {
            $out[] = $this->line($this->pair($pay->method_name.($pay->reference ? ' #'.$pay->reference : ''), Money::format($pay->amount)));
            if ($pay->tendered !== null) {
                $out[] = $this->line($this->pair(' Cash received', Money::format($pay->tendered)));
            }
        }
        if (Money::of($order->change_total)->isPositive()) {
            $out[] = $this->line($this->pair('CHANGE', Money::format($order->change_total)), 'left', true);
        }
        $due = Money::of($order->grand_total)->minus(Money::of($order->paid_total));
        if ($order->status === 'cancelled') {
            $out[] = $this->line('*** CANCELLED ***', 'center', true);
        } elseif ($due->isPositive()) {
            $out[] = $this->line($this->pair('DUE', Money::format($due)), 'left', true);
        } else {
            $out[] = $this->line('PAID', 'center', true);
        }
        $out[] = ['divider' => true];
        if ($f = Setting::get('receipt_footer', 'Thank you! Come again.')) {
            foreach ($this->wrap($f) as $t) {
                $out[] = $this->line($t, 'center');
            }
        }
        $out[] = $this->line('Items: '.$order->items->sum('quantity'), 'center');

        return $out;
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
