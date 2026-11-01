<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\Enum\ConceptSide;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\Money;

final class LiquidationLine
{
    public function __construct(
        public readonly ConceptDefinition $concept,
        public readonly Money $base,
        public readonly string $quantity,
        public readonly ?int $divisor,
        public readonly string $formulaText,
        public readonly Money $amount,
    ) {}

    public static function of(ConceptDefinition $concept, Computation $computation): self
    {
        return new self(
            $concept,
            $computation->base,
            $computation->quantity,
            $computation->divisor,
            $computation->formulaText,
            $computation->amount,
        );
    }

    public function isEarning(): bool
    {
        return $this->concept->side === ConceptSide::Earning;
    }

    public function accruesSocialSecurity(): bool
    {
        return $this->concept->appliesSocialSecurity;
    }

    public function signedAmount(): Money
    {
        return $this->amount->times($this->concept->side->sign());
    }
}
