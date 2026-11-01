<?php

namespace App\Models;

/**
 * Implemented by every model backed by an editable parametric
 * catalog (see App\Http\Parametrics\ParametricRegistry).
 */
interface ParametricCatalog
{
    public function detailLabel(): string;
}
