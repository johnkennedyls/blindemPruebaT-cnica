<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\ValueObjects\Money;

final class IndemnizableDays
{
    public readonly Money $total;

    public function __construct(
        public readonly Money $firstYear,
        public readonly Money $followingYears,
        public readonly Money $fraction,
        public readonly string $detail,
    ) {
        $this->total = $firstYear->plus($followingYears)->plus($fraction);
    }

    public static function zero(): self
    {
        return new self(Money::zero(), Money::zero(), Money::zero(), 'Sin indemnización');
    }

    public static function of(Money $firstYear, Money $followingYears, Money $fraction, string $detail): self
    {
        return new self($firstYear, $followingYears, $fraction, $detail);
    }

    public static function single(Money $days, string $detail): self
    {
        return new self($days, Money::zero(), Money::zero(), $detail);
    }

    public function isZero(): bool
    {
        return $this->total->isZero();
    }

    public function amount(): string
    {
        return $this->total->exactAmount();
    }
}
