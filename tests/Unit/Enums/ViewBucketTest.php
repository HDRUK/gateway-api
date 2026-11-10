<?php

namespace Tests\Unit\Enums;

use App\Enums\ViewBucket;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ViewBucketTest extends TestCase
{
    public static function rangeLengths(): array
    {
        return [
            'same day'  => [0, ViewBucket::DAY],
            '29 days'   => [29, ViewBucket::DAY],
            '30 days'   => [30, ViewBucket::WEEK],
            '179 days'  => [179, ViewBucket::WEEK],
            '180 days'  => [180, ViewBucket::MONTH],
            '365 days'  => [365, ViewBucket::MONTH],
        ];
    }

    #[DataProvider('rangeLengths')]
    public function test_picks_bucket_from_range_length(int $days, ViewBucket $expected): void
    {
        $start = CarbonImmutable::parse('2024-01-01');

        $this->assertSame($expected, ViewBucket::forRange($start, $start->addDays($days)));
    }
}
