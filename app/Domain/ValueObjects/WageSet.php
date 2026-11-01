<?php

namespace App\Domain\ValueObjects;

final class WageSet
{
    public function __construct(
        public readonly int $year,
        public readonly Money $minimumWage,
        public readonly Money $transportAuxiliary,
    ) {}
}
