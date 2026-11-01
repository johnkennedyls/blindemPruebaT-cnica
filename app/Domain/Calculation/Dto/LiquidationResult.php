<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\ValueObjects\Money;
use LogicException;

final class LiquidationResult
{
    /**
     * @param  array<int, LiquidationLine>  $lines
     * @param  array<int, string>  $notices
     */
    public function __construct(
        public readonly array $lines,
        public readonly Money $earned,
        public readonly Money $deducted,
        public readonly Money $net,
        public readonly Money $socialSecurityBase,
        public readonly array $notices,
        public readonly LiquidationContext $context,
    ) {}

    /**
     * @param  array<int, string>  $notices
     */
    public static function assemble(ComputedLines $computed, array $notices, LiquidationContext $context): self
    {
        $earned = Money::zero();
        $deducted = Money::zero();
        $ssBase = Money::zero();

        foreach ($computed->all() as $line) {
            if (! $line->isEarning()) {
                $deducted = $deducted->plus($line->amount);

                continue;
            }

            $earned = $earned->plus($line->amount);

            if ($line->accruesSocialSecurity()) {
                $ssBase = $ssBase->plus($line->amount);
            }
        }

        $net = $earned->minus($deducted);

        if (! $earned->equals($net->plus($deducted))) {
            throw new LogicException('Los totales de la liquidación no cuadran.');
        }

        return new self($computed->all(), $earned, $deducted, $net, $ssBase, $notices, $context);
    }

    /**
     * @return array<int, LiquidationLine>
     */
    public function earnings(): array
    {
        return array_values(array_filter($this->lines, fn (LiquidationLine $line): bool => $line->isEarning()));
    }

    /**
     * @return array<int, LiquidationLine>
     */
    public function deductions(): array
    {
        return array_values(array_filter($this->lines, fn (LiquidationLine $line): bool => ! $line->isEarning()));
    }

    public function line(string $formulaKey): ?LiquidationLine
    {
        foreach ($this->lines as $line) {
            if ($line->concept->formulaKey === $formulaKey) {
                return $line;
            }
        }

        return null;
    }

    public function amount(string $formulaKey): Money
    {
        $line = $this->line($formulaKey);

        return $line === null ? Money::zero() : $line->amount;
    }
}
