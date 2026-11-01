<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\ValueObjects\Money;

final class Computation
{
    public function __construct(
        public readonly Money $base,
        public readonly string $quantity,
        public readonly ?int $divisor,
        public readonly string $formulaText,
        public readonly Money $amount,
    ) {}
}
