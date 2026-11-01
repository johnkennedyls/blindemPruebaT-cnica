<?php

namespace App\Domain\Enum;

enum ConceptSide: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';

    public function label(): string
    {
        return $this === self::Earning ? 'Devengado' : 'Deducción';
    }

    public function sign(): int
    {
        return $this === self::Earning ? 1 : -1;
    }
}
