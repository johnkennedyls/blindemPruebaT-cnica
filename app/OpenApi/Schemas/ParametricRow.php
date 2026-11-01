<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Fila del listado de un catálogo paramétrico (`ParametricController::row`).
 */
#[OA\Schema(
    schema: 'ParametricRow',
    title: 'Registro paramétrico',
    description: 'Fila genérica del listado de catálogos (tipos de documento, cargos, tipos de contrato, motivos de retiro, salarios mínimos).',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'headline', type: 'string', example: 'CC',
            description: 'Encabezado de la fila (código o año según catálogo).'),
        new OA\Property(property: 'primary', type: 'string', example: 'Cédula de ciudadanía'),
        new OA\Property(property: 'secondary', type: 'string', nullable: true,
            description: 'Detalle humano del registro (según `detailLabel()` del modelo).'),
        new OA\Property(property: 'is_active', type: 'boolean', nullable: true,
            description: 'Estado de pausa; `null` en catálogos sin estado.'),
        new OA\Property(property: 'usage_count', type: 'integer', nullable: true, example: 3,
            description: 'Registros que dependen del item (`null` salarios mínimos, no tiene dependencia).'),
    ],
)]
final class ParametricRow {}
