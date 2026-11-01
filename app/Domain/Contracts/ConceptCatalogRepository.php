<?php

namespace App\Domain\Contracts;

use App\Domain\ValueObjects\ConceptList;

interface ConceptCatalogRepository
{
    public function activeConcepts(): ConceptList;
}
