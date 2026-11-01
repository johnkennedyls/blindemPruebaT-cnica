<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Enum\IndemnizationRule;

final class IndefinidoMenos10Smlmv extends ProportionalByYears
{
    public function __construct()
    {
        parent::__construct(
            IndemnizationRule::Art64LiteralA,
            'indemnization_first_year_days_low',
            'indemnization_per_year_days_low',
        );
    }
}
