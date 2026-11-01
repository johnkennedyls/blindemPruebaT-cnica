<?php

namespace App\Domain\ValueObjects;

use App\Domain\Enum\ConceptSide;
use Countable;
use InvalidArgumentException;

final class ConceptList implements Countable
{
    /**
     * @var array<int, ConceptDefinition>
     */
    private readonly array $concepts;

    private function __construct(array $concepts)
    {
        $this->concepts = array_values($concepts);
    }

    public static function fromArray(array $concepts): self
    {
        foreach ($concepts as $concept) {
            if (! $concept instanceof ConceptDefinition) {
                throw new InvalidArgumentException('La lista solo acepta definiciones de concepto.');
            }
        }

        return new self($concepts);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function inOrder(): array
    {
        $ordered = $this->concepts;

        usort($ordered, fn (ConceptDefinition $a, ConceptDefinition $b): int => $a->sequence <=> $b->sequence);

        return $ordered;
    }

    public function earning(): array
    {
        return $this->filterBySide(ConceptSide::Earning);
    }

    public function deductions(): array
    {
        return $this->filterBySide(ConceptSide::Deduction);
    }

    public function count(): int
    {
        return count($this->concepts);
    }

    private function filterBySide(ConceptSide $side): array
    {
        return array_values(array_filter(
            $this->concepts,
            fn (ConceptDefinition $concept): bool => $concept->side === $side,
        ));
    }
}
