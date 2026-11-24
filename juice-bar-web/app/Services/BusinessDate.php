<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Converts a moment in time into the business date (Asia/Dhaka by default).
 * If business_day_starts_at_hour is e.g. 4, an order at 02:30 belongs to the previous day.
 */
class BusinessDate
{
    public static function for(?CarbonInterface $moment = null): CarbonImmutable
    {
        $local = CarbonImmutable::instance($moment ?? now())->setTimezone(config('pos.timezone'));
        $startHour = (int) config('pos.business_day_starts_at_hour', 0);

        if ($local->hour < $startHour) {
            $local = $local->subDay();
        }

        return $local->startOfDay();
    }
}
