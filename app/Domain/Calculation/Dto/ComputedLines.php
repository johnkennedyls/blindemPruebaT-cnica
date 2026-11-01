<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\Money;
use Countable;

final class ComputedLines implements Countable
{
    /**
     * @var array<string, LiquidationLine>
     */
    private readonly array $byFormulaKey;

    /**
     * @var array<int, LiquidationLine>
     */
    private readonly array $ordered;

    /**
     * @param  array<string, LiquidationLine>  $byFormulaKey
     * @param  array<int, LiquidationLine>  $ordered
     */
    private function __construct(array $byFormulaKey, array $ordered)
    {
        $this->byFormulaKey = $byFormulaKey;
        $this->ordered = $ordered;
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    public function with(ConceptDefinition $concept, LiquidationLine $line): self
    {
        $indexed = $this->byFormulaKey;
        $indexed[$concept->formulaKey] = $line;

        $ordered = $this->ordered;
        $ordered[] = $line;

        return new self($indexed, $ordered);
    }

    public function line(string $formulaKey): ?LiquidationLine
    {
        return $this->byFormulaKey[$formulaKey] ?? null;
    }

    public function amount(string $formulaKey): Money
    {
        $line = $this->byFormulaKey[$formulaKey] ?? null;

        return $line === null ? Money::zero() : $line->amount;
    }

    public function socialSecurityBase(): Money
    {
        $base = Money::zero();

        foreach ($this->ordered as $line) {
            if ($line->isEarning() && $line->accruesSocialSecurity()) {
                $base = $base->plus($line->amount);
            }
        }

        return $base;
    }

    /**
     * @return array<int, LiquidationLine>
     */
    public function all(): array
    {
        return $this->ordered;
    }

    public function count(): int
    {
        return count($this->ordered);
    }
}
