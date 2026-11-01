<?php

namespace App\Domain\Calculation;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\DayCounts;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Calculation\Dto\LiquidationLine;
use App\Domain\Calculation\Dto\LiquidationResult;
use App\Domain\Calculation\Dto\Tenure;
use App\Domain\Calculation\Indemnization\IndemnizationStrategyFactory;
use App\Domain\Contracts\ConceptCatalogRepository;
use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\Contracts\MinimumWageRepository;
use App\Domain\ValueObjects\ConceptDefinition;

final class LiquidationEngine
{
    /**
     * @var array<string, ConceptCalculator>
     */
    private array $calculators = [];

    /**
     * @param  array<int, ConceptCalculator>  $calculators
     */
    public function __construct(
        private readonly ConceptCatalogRepository $catalog,
        private readonly MinimumWageRepository $minimumWages,
        private readonly LegalParameterRepository $parameters,
        private readonly IndemnizationStrategyFactory $strategies,
        array $calculators,
    ) {
        foreach ($calculators as $calculator) {
            $this->calculators[$calculator->key()] = $calculator;
        }
    }

    public function calculate(LiquidationDraft $draft): LiquidationResult
    {
        $parameters = new Parameters($this->parameters, $draft->settlementDate);
        $wages = $this->minimumWages->forDate($draft->settlementDate);
        $assembly = SalaryBaseBuilder::of($draft, $wages, $parameters);

        $commercialYear = WorkedDaysCalculator::commercialFromYearStart($draft->settlementDate);
        $commercialSemester = WorkedDaysCalculator::commercialFromSemesterStart($draft->settlementDate);
        $naturalDays = WorkedDaysCalculator::naturalBetween($draft->startDate, $draft->settlementDate);

        $days = new DayCounts(
            $commercialYear,
            $commercialSemester,
            $naturalDays,
            $draft->cesantiasDays ?? $commercialYear,
            $draft->primaDays ?? $commercialYear,
            $draft->vacationDays,
            $draft->pendingSalaryDays,
        );

        $context = new LiquidationContext(
            $draft,
            $assembly->bases,
            $days,
            Tenure::fromTotalDays($naturalDays, $parameters->days('fractional_year_days')),
            $wages,
            $parameters,
            $this->strategies->make(
                $draft->indemnizationRule,
                $draft->modality,
                $assembly->bases->basic,
                $wages->minimumWage,
                $parameters,
            ),
            $this->remainingTermDays($draft),
        );

        $computed = ComputedLines::empty();
        $notices = $assembly->notices;

        foreach ($this->catalog->activeConcepts()->inOrder() as $concept) {
            $calculator = $this->calculatorFor($concept);

            if ($calculator === null) {
                $notices[] = sprintf('El concepto %s usa la fórmula %s, que el sistema no conoce.', $concept->name, $concept->formulaKey);

                continue;
            }

            if (! $calculator->computes($context, $computed, $concept)) {
                continue;
            }

            $computed = $computed->with($concept, LiquidationLine::of($concept, $calculator->compute($context, $computed, $concept)));
        }

        return LiquidationResult::assemble($computed, $notices, $context);
    }

    private function calculatorFor(ConceptDefinition $concept): ?ConceptCalculator
    {
        return $this->calculators[$concept->formulaKey] ?? null;
    }

    private function remainingTermDays(LiquidationDraft $draft): int
    {
        if ($draft->scheduledEndDate === null) {
            return 0;
        }

        return WorkedDaysCalculator::remainingBetween($draft->settlementDate, $draft->scheduledEndDate);
    }
}
