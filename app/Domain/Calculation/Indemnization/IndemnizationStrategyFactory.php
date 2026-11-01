<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Calculation\Contracts\IndemnizationStrategy;
use App\Domain\Calculation\Parameters;
use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\ValueObjects\Money;

final class IndemnizationStrategyFactory
{
    public function make(
        IndemnizationRule $rule,
        ContractModality $modality,
        Money $salary,
        Money $minimumWage,
        Parameters $parameters,
    ): IndemnizationStrategy {
        if ($rule === IndemnizationRule::Ninguna) {
            return new SinIndemnizacion;
        }

        if ($modality === ContractModality::TerminoFijo) {
            return new TerminoFijoPlazoRestante;
        }

        if ($rule === IndemnizationRule::Art64LiteralA) {
            return new IndefinidoMenos10Smlmv;
        }

        if ($rule === IndemnizationRule::Art64LiteralB) {
            return new IndefinidoMas10Smlmv;
        }

        if ($rule === IndemnizationRule::PlazoRestante) {
            return new TerminoFijoPlazoRestante;
        }

        $threshold = $minimumWage->times($parameters->rate('indemnization_threshold_multiple')->exactAmount());

        return $salary->atLeast($threshold)
            ? new IndefinidoMas10Smlmv
            : new IndefinidoMenos10Smlmv;
    }
}
