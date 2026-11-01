<?php

namespace App\Domain\Calculation;

use DateTimeImmutable;

final class WorkedDaysCalculator
{
    private const COMMERCIAL_MONTH = 30;

    private const FIRST_SEMESTER_MONTH = 7;

    public static function commercialFromYearStart(string $date): int
    {
        $day = new DateTimeImmutable($date);

        return self::commercialCount((int) $day->format('n'), (int) $day->format('j'));
    }

    public static function commercialFromSemesterStart(string $date): int
    {
        $day = new DateTimeImmutable($date);
        $month = (int) $day->format('n');

        return self::commercialCount(
            $month >= self::FIRST_SEMESTER_MONTH ? $month - self::FIRST_SEMESTER_MONTH + 1 : $month,
            (int) $day->format('j'),
        );
    }

    public static function naturalBetween(string $startDate, string $endDate): int
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        $diff = $start->diff($end, true);

        return (int) $diff->format('%r%a') + 1;
    }

    public static function remainingBetween(string $from, string $to): int
    {
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);

        if ($end < $start) {
            return 0;
        }

        return (int) $start->diff($end, true)->format('%a');
    }

    private static function commercialCount(int $month, int $dayOfMonth): int
    {
        return self::COMMERCIAL_MONTH * ($month - 1) + ($dayOfMonth - 1) + 1;
    }
}
