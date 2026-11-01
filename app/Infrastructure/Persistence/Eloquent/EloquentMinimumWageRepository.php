<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Contracts\MinimumWageRepository;
use App\Domain\Exceptions\WageNotConfigured;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\WageSet;
use App\Models\MinimumWage;

final class EloquentMinimumWageRepository implements MinimumWageRepository
{
    public function forYear(int $year): WageSet
    {
        $wage = MinimumWage::query()->where('year', $year)->first();

        if ($wage === null) {
            throw WageNotConfigured::missingYear($year);
        }

        return $this->toWageSet($wage);
    }

    public function forDate(string $date): WageSet
    {
        $wage = MinimumWage::query()
            ->whereDate('effective_from', '<=', $date)
            ->whereDate('effective_to', '>=', $date)
            ->orderByDesc('effective_from')
            ->first();

        if ($wage === null) {
            throw WageNotConfigured::missingDate($date);
        }

        return $this->toWageSet($wage);
    }

    private function toWageSet(MinimumWage $wage): WageSet
    {
        return new WageSet(
            $wage->year,
            Money::of($wage->smlmv),
            Money::of($wage->transport_auxiliary),
        );
    }
}
