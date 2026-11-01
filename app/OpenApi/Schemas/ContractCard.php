<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Tarjeta de contrato que produce `App\Http\Presenters\ContractPresenter::card`.
 */
#[OA\Schema(
    schema: 'ContractCard',
    title: 'Contrato (tarjeta)',
    description: 'Transmisión del contrato usada por páginas de contratos y liquidaciones.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'code', type: 'string', example: 'CT-2024-001'),
        new OA\Property(property: 'status', ref: '#/components/schemas/LabeledValue',
            description: 'Estado actual del contrato con su etiqueta.'),
        new OA\Property(property: 'can_liquidate', type: 'boolean', example: true),
        new OA\Property(
            property: 'worker',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'name', type: 'string', example: 'Juan Carlos Pérez'),
                new OA\Property(property: 'document', type: 'string', example: 'CC 1098765432'),
                new OA\Property(property: 'document_type_id', type: 'integer', example: 1),
                new OA\Property(property: 'document_number', type: 'string', example: '1098765432'),
                new OA\Property(property: 'email', type: 'string', nullable: true, example: 'jperez@blindem.test'),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'company', type: 'string', example: 'Blindem S.A.S.'),
        new OA\Property(property: 'position', type: 'string', nullable: true, example: 'Analista Operativo'),
        new OA\Property(property: 'contract_type', type: 'string', nullable: true, example: 'Indefinido'),
        new OA\Property(property: 'modality', type: 'string', nullable: true, example: 'Indefinido'),
        new OA\Property(property: 'termination_reason', type: 'string', nullable: true, example: 'Despido sin justa causa'),
        new OA\Property(property: 'applies_indemnization', type: 'boolean', example: true),
        new OA\Property(
            property: 'dates',
            properties: [
                new OA\Property(property: 'start', type: 'string', format: 'date', example: '2023-01-15'),
                new OA\Property(property: 'end', type: 'string', format: 'date', nullable: true, example: '2024-06-30'),
                new OA\Property(property: 'scheduled_end', type: 'string', format: 'date', nullable: true),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'salary',
            description: 'Salario y variables según contrato (cadenas decimales).',
            properties: [
                new OA\Property(property: 'basic', type: 'string', example: '1750905.00'),
                new OA\Property(property: 'auxiliary', type: 'string', example: '249095.00'),
                new OA\Property(property: 'overtime_average', type: 'string', example: '150000.00'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'pending_days',
            properties: [
                new OA\Property(property: 'vacations', type: 'integer', example: 15),
                new OA\Property(property: 'salary', type: 'integer', example: 15),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'liquidations',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 12),
                    new OA\Property(property: 'folio', type: 'string', example: 'LIQ-2024-00012'),
                    new OA\Property(property: 'net', type: 'string', example: '3180899.00'),
                    new OA\Property(property: 'status', type: 'string', example: 'Guardada'),
                    new OA\Property(property: 'settlement_date', type: 'string', format: 'date', example: '2024-06-30'),
                ],
                type: 'object',
            ),
        ),
    ],
)]
final class ContractCard {}
