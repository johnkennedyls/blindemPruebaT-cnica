<?php

namespace App\Domain\Calculation\Dto;

final class Tenure
{
    public function __construct(
        public readonly int $totalDays,
        public readonly int $completeYears,
        public readonly int $fractionDays,
        public readonly int $yearDays,
    ) {}

    public static function fromTotalDays(int $totalDays, int $yearDays): self
    {
        return new self(
            $totalDays,
            intdiv(max(0, $totalDays), $yearDays),
            max(0, $totalDays) % $yearDays,
            $yearDays,
        );
    }

    public function isUnderOneYear(): bool
    {
        return $this->completeYears < 1;
    }
}
