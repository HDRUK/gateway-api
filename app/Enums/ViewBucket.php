<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum ViewBucket
{
    case DAY;
    case WEEK;
    case MONTH;

    public static function forRange(CarbonImmutable $start, CarbonImmutable $end): self
    {
        $days = $start->diffInDays($end);

        return match (true) {
            $days >= 180 => self::MONTH,
            $days >= 30  => self::WEEK,
            default      => self::DAY,
        };
    }

    public function sqlLabel(): string
    {
        return match ($this) {
            self::DAY   => "FORMAT_DATE('%Y-%m-%d', date)",
            self::WEEK  => "FORMAT_DATE('%Y-%W', DATE_TRUNC(date, WEEK))",
            self::MONTH => "FORMAT_DATE('%Y-%m', DATE_TRUNC(date, MONTH))",
        };
    }
}
