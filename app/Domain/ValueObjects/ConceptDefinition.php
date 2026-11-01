<?php

namespace App\Domain\ValueObjects;

use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ConceptSide;

final class ConceptDefinition
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ConceptSide $side,
        public readonly string $formulaKey,
        public readonly BaseKind $baseKind,
        public readonly ?int $defaultDivisor,
        public readonly bool $appliesSocialSecurity,
        public readonly int $sequence,
    ) {
    }
}
