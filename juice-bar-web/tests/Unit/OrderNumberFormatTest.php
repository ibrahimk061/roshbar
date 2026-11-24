<?php

namespace Tests\Unit;

use App\Services\OrderNumberGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderNumberFormatTest extends TestCase
{
    #[DataProvider('blocks')]
    public function test_block_letters(int $index, string $expected): void
    {
        $this->assertSame($expected, OrderNumberGenerator::blockLetters($index));
    }

    public static function blocks(): array
    {
        return [[0, 'A'], [1, 'B'], [25, 'Z'], [26, 'AA'], [27, 'AB'], [51, 'AZ'], [52, 'BA'], [701, 'ZZ'], [702, 'AAA']];
    }
}
