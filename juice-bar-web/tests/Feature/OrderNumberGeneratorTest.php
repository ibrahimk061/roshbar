<?php

namespace Tests\Feature;

use App\Services\BusinessDate;
use App\Services\OrderNumberGenerator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_follows_blocks_of_100(): void
    {
        $gen = app(OrderNumberGenerator::class);
        $d = CarbonImmutable::create(2026, 11, 24);

        $this->assertSame('RB-24-11-26-A0001', $gen->format($d, 1));
        $this->assertSame('RB-24-11-26-A0002', $gen->format($d, 2));
        $this->assertSame('RB-24-11-26-A0100', $gen->format($d, 100));
        $this->assertSame('RB-24-11-26-B0001', $gen->format($d, 101));
        $this->assertSame('RB-24-11-26-B0002', $gen->format($d, 102));
        $this->assertSame('RB-24-11-26-Z0100', $gen->format($d, 2600));
        $this->assertSame('RB-24-11-26-AA0001', $gen->format($d, 2601));
    }

    public function test_sequence_increments_and_resets_per_business_date(): void
    {
        $gen = app(OrderNumberGenerator::class);
        $day1 = CarbonImmutable::create(2026, 11, 24);
        $day2 = CarbonImmutable::create(2026, 11, 25);

        $numbers = DB::transaction(fn () => [
            $gen->next($day1)['number'],
            $gen->next($day1)['number'],
            $gen->next($day2)['number'],
            $gen->next($day1)['number'],
        ]);

        $this->assertSame(['RB-24-11-26-A0001', 'RB-24-11-26-A0002', 'RB-25-11-26-A0001', 'RB-24-11-26-A0003'], $numbers);
    }

    public function test_must_run_inside_a_transaction(): void
    {
        $this->expectException(\LogicException::class);
        // RefreshDatabase wraps tests in a transaction, so step out of it.
        DB::commit();
        try {
            app(OrderNumberGenerator::class)->next(CarbonImmutable::now());
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_business_date_uses_dhaka_midnight(): void
    {
        // 17:59 UTC = 23:59 Dhaka on the 24th; 18:00 UTC = 00:00 Dhaka on the 25th.
        $this->assertSame('2026-11-24', BusinessDate::for(Carbon::parse('2026-11-24 17:59:59', 'UTC'))->format('Y-m-d'));
        $this->assertSame('2026-11-25', BusinessDate::for(Carbon::parse('2026-11-24 18:00:00', 'UTC'))->format('Y-m-d'));
    }

    public function test_business_day_start_hour_is_configurable(): void
    {
        config(['pos.business_day_starts_at_hour' => 4]);
        // 02:30 Dhaka on the 25th still belongs to the 24th.
        $this->assertSame('2026-11-24', BusinessDate::for(Carbon::parse('2026-11-24 20:30:00', 'UTC'))->format('Y-m-d'));
        $this->assertSame('2026-11-25', BusinessDate::for(Carbon::parse('2026-11-24 22:00:00', 'UTC'))->format('Y-m-d'));
    }
}
