<?php

namespace App\Domain\Contracts;

use App\Domain\ValueObjects\WageSet;

interface MinimumWageRepository
{
    public function forYear(int $year): WageSet;

    public function forDate(string $date): WageSet;
}
