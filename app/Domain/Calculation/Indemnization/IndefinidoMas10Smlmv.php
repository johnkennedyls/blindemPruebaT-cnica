<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Enum\IndemnizationRule;

final class IndefinidoMas10Smlmv extends ProportionalByYears
{
    public function __construct()
    {
        parent::__construct(
            IndemnizationRule::Art64LiteralB,
            'indemnization_first_year_days_high',
            'indemnization_per_year_days_high',
        );
    }
}
