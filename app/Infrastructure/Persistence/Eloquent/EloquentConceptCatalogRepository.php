<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Contracts\ConceptCatalogRepository;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\ConceptList;
use App\Models\ConceptCatalog;

final class EloquentConceptCatalogRepository implements ConceptCatalogRepository
{
    public function activeConcepts(): ConceptList
    {
        $concepts = ConceptCatalog::query()
            ->active()
            ->get()
            ->map(fn (ConceptCatalog $concept): ConceptDefinition => new ConceptDefinition(
                $concept->code,
                $concept->name,
                $concept->side,
                $concept->formula_key,
                $concept->base_kind,
                $concept->default_divisor,
                $concept->applies_ss_contribution,
                $concept->default_sequence,
            ))
            ->all();

        return ConceptList::fromArray($concepts);
    }
}
