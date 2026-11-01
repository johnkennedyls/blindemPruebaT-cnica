<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Par de valor/etiqueta que la app entrega a la SPA para selects y badges.
 */
#[OA\Schema(
    schema: 'LabeledValue',
    title: 'Opción etiquetada',
    description: 'Par `value`/`label` usado por Inertia en filtros, selects y listados.',
    type: 'object',
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'activo'),
        new OA\Property(property: 'label', type: 'string', example: 'Activo'),
    ],
)]
final class LabeledValue {}
