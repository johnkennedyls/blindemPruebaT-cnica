<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Documento completo de liquidación (`LiquidationPresenter::document`).
 */
#[OA\Schema(
    schema: 'LiquidationDocument',
    title: 'Liquidación (documento)',
    description: 'Snapshot completo de la liquidación guardada: datos del formato, líneas con traza, totales y parámetros capturados al momento del cálculo.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'folio', type: 'string', example: 'LIQ-2024-00012'),
        new OA\Property(property: 'status', ref: '#/components/schemas/LabeledValue', example: ['value' => 'guardada', 'label' => 'Guardada']),
        new OA\Property(property: 'variant', ref: '#/components/schemas/LabeledValue', example: ['value' => 'con_indemnizacion', 'label' => 'Con indemnización']),
        new OA\Property(property: 'observations', type: 'string', nullable: true),
        new OA\Property(property: 'creator', type: 'string', nullable: true, example: 'Administrador Blindem'),
        new OA\Property(
            property: 'dates',
            properties: [
                new OA\Property(property: 'settlement', type: 'string', format: 'date', example: '2024-06-30'),
                new OA\Property(property: 'period_from', type: 'string', format: 'date', nullable: true, example: '2021-03-15'),
                new OA\Property(property: 'period_to', type: 'string', format: 'date', nullable: true, example: '2024-06-30'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'bases',
            description: 'Bases tomadas del contrato y del SMLMV vigente a la fecha de liquidación.',
            properties: [
                new OA\Property(property: 'prestacional', type: 'string', example: '2150000.00'),
                new OA\Property(property: 'vacacional', type: 'string', example: '1900905.00'),
                new OA\Property(property: 'smlmv', type: 'string', example: '1300000.00'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'days',
            properties: [
                new OA\Property(property: 'cesantias', type: 'integer', example: 180),
                new OA\Property(property: 'prima', type: 'integer', example: 180),
                new OA\Property(property: 'vacaciones', type: 'integer', example: 15),
                new OA\Property(property: 'salario_pendiente', type: 'integer', example: 15),
                new OA\Property(property: 'worked', type: 'integer', example: 1204,
                    description: 'Días naturales totales trabajados entre ingreso y retiro.'),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'lines', type: 'array', items: new OA\Items(ref: '#/components/schemas/LiquidationLine')),
        new OA\Property(
            property: 'totals',
            properties: [
                new OA\Property(property: 'earned', type: 'string', example: '7688130.00',
                    description: 'Total devengado (suma del lado devengados).'),
                new OA\Property(property: 'deducted', type: 'string', example: '73204.00',
                    description: 'Total deducciones (aportes 4% y descuentos manuales).'),
                new OA\Property(property: 'net', type: 'string', example: '7614926.00',
                    description: 'Neto a pagar, coincide con la celda del Excel del cliente.'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'notices',
            type: 'array',
            description: 'Avisos del motor (p. ej. salario > 2 SMLMV elimina el auxilio de transporte).',
            items: new OA\Items(type: 'string'),
        ),
        new OA\Property(
            property: 'indemnization',
            type: 'object',
            nullable: true,
            description: 'Bloque del formato de indemnización (Formato B): regla aplicada, años completos, fracción, días indemnizables y valor.',
            properties: [
                new OA\Property(property: 'regla', type: 'string', example: 'Salario menor a 10 SMLMV — literal A, artículo 64 CST'),
                new OA\Property(property: 'anos_completos', type: 'integer', example: 3),
                new OA\Property(property: 'fraccion', type: 'integer', example: 109),
                new OA\Property(property: 'dias_del_ano', type: 'integer', example: 365),
                new OA\Property(property: 'dias_indemnizables', type: 'string', example: '75.972602739726'),
            ],
        ),
        new OA\Property(
            property: 'tenure',
            type: 'object',
            nullable: true,
            description: 'Antigüedad calculada: años completos, fracción y días del año usados por las reglas del artículo 64.',
        ),
        new OA\Property(
            property: 'wages',
            type: 'object',
            nullable: true,
            description: 'SMLMV y auxilio usados en el cálculo.',
        ),
        new OA\Property(
            property: 'parameters',
            type: 'object',
            description: 'Tasas y divisores legales congelados al momento del cálculo.',
        ),
        new OA\Property(property: 'captured_at', type: 'string', nullable: true, format: 'date-time'),
    ],
)]
final class LiquidationDocument {}
