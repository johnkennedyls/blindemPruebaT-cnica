<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\Exceptions\ParameterNotConfigured;
use App\Models\LegalParameter;

final class EloquentLegalParameterRepository implements LegalParameterRepository
{
    public function value(string $key, string $asOfDate): string
    {
        $parameter = LegalParameter::query()
            ->where('key', $key)
            ->whereDate('effective_from', '<=', $asOfDate)
            ->where(function ($query) use ($asOfDate): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $asOfDate);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($parameter === null) {
            throw ParameterNotConfigured::missing($key, $asOfDate);
        }

        return $parameter->value();
    }
}
