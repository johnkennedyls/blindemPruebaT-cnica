<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\ValueObjects\Money;

final class SalaryBases
{
    public function __construct(
        public readonly Money $basic,
        public readonly Money $transport,
        public readonly Money $variables,
        public readonly Money $prestacional,
        public readonly Money $vacacional,
    ) {}
}
