<?php

namespace App\Domain\Calculation;

use App\Domain\Calculation\Dto\SalaryBaseAssembly;
use App\Domain\Calculation\Dto\SalaryBases;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\WageSet;

final class SalaryBaseBuilder
{
    public static function of(LiquidationDraft $draft, WageSet $wages, Parameters $parameters): SalaryBaseAssembly
    {
        $limit = $wages->minimumWage->times($parameters->rate('transport_auxiliary_max_multiple')->exactAmount());
        $notices = [];

        $transport = match ($draft->transportMode) {
            TransportAuxiliaryMode::Auto => $wages->transportAuxiliary,
            TransportAuxiliaryMode::Manual => $draft->transportAuxiliary,
            TransportAuxiliaryMode::SinDerecho => Money::zero(),
        };

        if ($transport->isNegative()) {
            $notices[] = 'El auxilio de transporte digitado es negativo; se usó cero.';
            $transport = Money::zero();
        }

        if (! $draft->baseSalary->atMost($limit)) {
            if (! $transport->isZero()) {
                $notices[] = sprintf(
                    'El salario básico supera %s (%s SMLMV del año %d); no hay derecho a auxilio de transporte.',
                    $limit->amount(),
                    $parameters->rate('transport_auxiliary_max_multiple')->displayAmount(),
                    $wages->year,
                );
            }

            $transport = Money::zero();
        }

        $bases = new SalaryBases(
            $draft->baseSalary,
            $transport,
            $draft->overtimeAverage,
            $draft->baseSalary->plus($transport)->plus($draft->overtimeAverage),
            $draft->baseSalary->plus($draft->overtimeAverage),
        );

        return new SalaryBaseAssembly($bases, $notices);
    }
}
