<?php

namespace App\Services;

use App\Models\OrderSequence;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Issues official order numbers: {PREFIX}-DD-MM-YY-{BLOCK}{NNNN}
 *   #1..#100   -> A0001..A0100
 *   #101..#200 -> B0001..B0100
 *   ... Z, then AA, AB, ...
 *
 * Must be called inside a DB transaction. The per-day sequence row is
 * locked FOR UPDATE so concurrent checkouts never get the same number.
 * Numbers are never released (cancelled orders keep theirs).
 */
class OrderNumberGenerator
{
    /** @return array{number: string, sequence: int} */
    public function next(CarbonInterface $businessDate): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('OrderNumberGenerator::next() must run inside a transaction.');
        }

        $date = $businessDate->format('Y-m-d');

        // Make sure the row exists, then lock it.
        OrderSequence::query()->insertOrIgnore([
            'business_date' => $date,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = OrderSequence::query()->whereDate('business_date', $date)->lockForUpdate()->firstOrFail();
        $sequence = $row->last_number + 1;
        $row->update(['last_number' => $sequence]);

        return ['number' => $this->format($businessDate, $sequence), 'sequence' => $sequence];
    }

    public function format(CarbonInterface $businessDate, int $sequence): string
    {
        $perBlock = (int) config('pos.orders_per_block', 100);
        $blockIndex = intdiv($sequence - 1, $perBlock);
        $within = (($sequence - 1) % $perBlock) + 1;
        $prefix = Setting::get('order_prefix', config('pos.order_prefix'));

        return sprintf('%s-%s-%s%04d', $prefix, $businessDate->format('d-m-y'), self::blockLetters($blockIndex), $within);
    }

    /** 0 => A, 25 => Z, 26 => AA, 27 => AB ... (bijective base-26) */
    public static function blockLetters(int $index): string
    {
        $letters = '';
        $n = $index + 1;
        while ($n > 0) {
            $n--;
            $letters = chr(65 + ($n % 26)).$letters;
            $n = intdiv($n, 26);
        }

        return $letters;
    }
}
