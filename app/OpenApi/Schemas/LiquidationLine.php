<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Línea de cálculo dentro del documento de liquidación (`LiquidationPresenter::document`).
 */
#[OA\Schema(
    schema: 'LiquidationLine',
    title: 'Línea de liquidación',
    description: 'Cada renglón del formato estándar con su traza de cálculo, conforme al Excel del cliente.',
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'CESANTIAS'),
        new OA\Property(property: 'name', type: 'string', example: 'Cesantías'),
        new OA\Property(property: 'side', ref: '#/components/schemas/LabeledValue',
            description: 'Devengado (`devengado`) o deducción (`deduccion`).'),
        new OA\Property(property: 'base', type: 'string', nullable: true, example: '2150000',
            description: 'Base de cálculo (celda referenciada en el formato).'),
        new OA\Property(property: 'quantity', type: 'string', nullable: true, example: '180',
            description: 'Días o unidades aplicadas al cálculo.'),
        new OA\Property(property: 'divisor', type: 'integer', nullable: true, example: 360,
            description: 'Divisor del formato (360 cesantías/prima, 720 vacaciones, 30 pagos).'),
        new OA\Property(property: 'formula', type: 'string', nullable: true, example: 'Base prestacional × Días / 360'),
        new OA\Property(property: 'trace', type: 'string', nullable: true, example: '2150000 × 180 / 360 = 1075000',
            description: 'Trazabilidad numérica del cálculo reproducida del Excel.'),
        new OA\Property(property: 'amount', type: 'string', example: '1075000.00'),
        new OA\Property(property: 'accrues_social_security', type: 'boolean', example: true,
            description: 'Si el concepto alimenta la base de aportes 4% (salud y pensión).'),
    ],
)]
final class LiquidationLine {}
