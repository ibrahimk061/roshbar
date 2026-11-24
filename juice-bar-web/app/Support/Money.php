<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * All money math goes through BigDecimal. Never use PHP floats for amounts.
 */
final class Money
{
    public static function of(string|int|BigDecimal|null $amount): BigDecimal
    {
        return BigDecimal::of($amount ?? '0')->toScale(2, RoundingMode::HalfUp);
    }

    public static function zero(): BigDecimal
    {
        return BigDecimal::zero()->toScale(2);
    }

    public static function str(BigDecimal $amount): string
    {
        return (string) $amount->toScale(2, RoundingMode::HalfUp);
    }

    /** "1,250.00" style display. */
    public static function format(string|BigDecimal|null $amount): string
    {
        $value = self::str(self::of($amount instanceof BigDecimal ? (string) $amount : $amount));
        [$int, $dec] = explode('.', $value);
        $negative = str_starts_with($int, '-');
        $int = ltrim($int, '-');
        $int = strrev(implode(',', str_split(strrev($int), 3)));

        return ($negative ? '-' : '').$int.'.'.$dec;
    }
}
