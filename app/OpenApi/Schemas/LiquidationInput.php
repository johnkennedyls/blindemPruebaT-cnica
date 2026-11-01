<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo de creación de liquidación (ver `App\Http\Requests\StoreLiquidationRequest`).
 */
#[OA\Schema(
    schema: 'LiquidationInput',
    title: 'Cuerpo de liquidación',
    description: 'Validación de `POST /contratos/{contract}/liquidar`. Los días enviados alimentan directamente al motor conforme al Excel del cliente (celdas E12/E13/E14); dejar `cesantias_days`/`prima_days` nulos los deriva del periodo.',
    required: ['settlement_date', 'variant', 'vacation_days', 'pending_salary_days'],
    properties: [
        new OA\Property(property: 'settlement_date', type: 'string', format: 'date', example: '2024-06-30',
            description: 'Fecha de liquidación (cese); no puede ser anterior al inicio del contrato.'),
        new OA\Property(property: 'variant', type: 'string', enum: ['prestacional', 'con_indemnizacion'],
            description: 'Formato A (prestaciones) o Formato B (prestaciones + indemnización).'),
        new OA\Property(property: 'cesantias_days', type: 'integer', minimum: 0, maximum: 360, nullable: true, example: 180),
        new OA\Property(property: 'prima_days', type: 'integer', minimum: 0, maximum: 180, nullable: true, example: 180),
        new OA\Property(property: 'vacation_days', type: 'integer', minimum: 0, maximum: 360, example: 15,
            description: 'Días de vacaciones pendientes (celda E12).'),
        new OA\Property(property: 'pending_salary_days', type: 'integer', minimum: 0, maximum: 360, example: 15,
            description: 'Días de salario/auxilio pendientes por pagar.'),
        new OA\Property(
            property: 'manual_values',
            type: 'object',
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(type: 'string', example: '0'),
            description: 'Valores digitados para conceptos manuales del catálogo (ej. `DESC_PRESTAMOS: "0"`).',
        ),
        new OA\Property(property: 'observations', type: 'string', maxLength: 2000, nullable: true),
    ],
)]
final class LiquidationInput {}
