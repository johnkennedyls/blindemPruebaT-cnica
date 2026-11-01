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

    /**
     * @param  array<int, ConceptDefinition>  $concepts
     */
    private function __construct(array $concepts)
    {
        /** @var array<int, ConceptDefinition> $reindexed */
        $reindexed = array_values($concepts);

        $this->concepts = $reindexed;
    }

    /**
     * @param  array<array-key, mixed>  $concepts
     */
    public static function fromArray(array $concepts): self
    {
        /** @var array<int, ConceptDefinition> $accepted */
        $accepted = [];

        foreach ($concepts as $concept) {
            if (! $concept instanceof ConceptDefinition) {
                throw new InvalidArgumentException('La lista solo acepta definiciones de concepto.');
            }

            $accepted[] = $concept;
        }

        return new self($accepted);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return array<int, ConceptDefinition>
     */
    public function inOrder(): array
    {
        $ordered = $this->concepts;

        usort($ordered, fn (ConceptDefinition $a, ConceptDefinition $b): int => $a->sequence <=> $b->sequence);

        return $ordered;
    }

    /**
     * @return array<int, ConceptDefinition>
     */
    public function earning(): array
    {
        return $this->filterBySide(ConceptSide::Earning);
    }

    /**
     * @return array<int, ConceptDefinition>
     */
    public function deductions(): array
    {
        return $this->filterBySide(ConceptSide::Deduction);
    }

    public function count(): int
    {
        return count($this->concepts);
    }

    /**
     * @return array<int, ConceptDefinition>
     */
    private function filterBySide(ConceptSide $side): array
    {
        /** @var array<int, ConceptDefinition> $filtered */
        $filtered = array_values(array_filter(
            $this->concepts,
            fn (ConceptDefinition $concept): bool => $concept->side === $side,
        ));

        return $filtered;
    }
}
