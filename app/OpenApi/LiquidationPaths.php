<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Liquidaciones: listado, formulario de cálculo, guardado, detalle y Excel.
 */
final class LiquidationPaths
{
    #[OA\Get(
        path: '/liquidaciones',
        operationId: 'liquidations.index',
        summary: 'Listado de liquidaciones/diario de liquidaciones en contratos',
        tags: ['Liquidaciones'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['activo', 'terminado', 'en_liquidacion', 'liquidado'])),
            new OA\Parameter(name: 'document_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'document_number', in: 'query', required: false, schema: new OA\Schema(type: 'string'),
                description: 'Número de documento del trabajador con coincidencia parcial.'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `liquidations/index`: contratos visibles con sus liquidaciones y los estados como filtros dinámicos.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'component', type: 'string', example: 'liquidations/index'),
                            new OA\Property(
                                property: 'props',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'contracts',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'current_page', type: 'integer'),
                                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ContractCard')),
                                            new OA\Property(property: 'total', type: 'integer'),
                                        ],
                                    ),
                                    new OA\Property(property: 'filters', type: 'object'),
                                    new OA\Property(property: 'statuses', type: 'array', items: new OA\Items(ref: '#/components/schemas/LabeledValue')),
                                    new OA\Property(property: 'documentTypes', type: 'array', items: new OA\Items(ref: '#/components/schemas/LabeledValue')),
                                ],
                            ),
                        ],
                    ),
                    example: [
                        'component' => 'liquidations/index',
                        'props' => [
                            'contracts' => [
                                'current_page' => 1,
                                'data' => [
                                    [
                                        'id' => 7,
                                        'code' => 'CT-2024-001',
                                        'status' => ['value' => 'liquidado', 'label' => 'Liquidado'],
                                        'worker' => ['id' => 3, 'name' => 'Juan Carlos Pérez', 'document' => 'CC 1098765432'],
                                        'company' => 'Blindem S.A.S.',
                                        'liquidations' => [
                                            ['id' => 12, 'folio' => 'LIQ-2024-00012', 'net' => '3180899.00', 'status' => 'Guardada', 'settlement_date' => '2024-06-30'],
                                        ],
                                    ],
                                ],
                                'total' => 1,
                            ],
                            'filters' => ['status' => null, 'document_type_id' => null, 'document_number' => null],
                            'statuses' => [
                                ['value' => 'activo', 'label' => 'Activo'],
                                ['value' => 'terminado', 'label' => 'Terminado'],
                                ['value' => 'en_liquidacion', 'label' => 'En liquidación'],
                                ['value' => 'liquidado', 'label' => 'Liquidado'],
                            ],
                            'documentTypes' => [['value' => 1, 'label' => 'CC · Cédula de ciudadanía']],
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/contratos/{contract}/liquidar',
        operationId: 'liquidations.create',
        summary: 'Formulario de liquidación de un contrato',
        tags: ['Liquidaciones'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 7),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `liquidations/create` con la tarjeta del contrato, los formatos disponibles y los conceptos manuales activos.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'component', type: 'string', example: 'liquidations/create'),
                            new OA\Property(
                                property: 'props',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'contract', ref: '#/components/schemas/ContractCard'),
                                    new OA\Property(property: 'variants', type: 'array', items: new OA\Items(ref: '#/components/schemas/LabeledValue')),
                                    new OA\Property(property: 'suggestedVariant', type: 'string'),
                                    new OA\Property(property: 'manualConcepts', type: 'array', items: new OA\Items(type: 'object')),
                                ],
                            ),
                        ],
                    ),
                    example: [
                        'component' => 'liquidations/create',
                        'props' => [
                            'contract' => [
                                'id' => 7,
                                'code' => 'CT-2024-001',
                                'status' => ['value' => 'terminado', 'label' => 'Terminado'],
                                'worker' => ['id' => 3, 'name' => 'Juan Carlos Pérez', 'document' => 'CC 1098765432'],
                                'company' => 'Blindem S.A.S.',
                                'dates' => ['start' => '2021-03-15', 'end' => '2024-06-30', 'scheduled_end' => null],
                                'salary' => ['basic' => '1750905.00', 'auxiliary' => '249095.00', 'overtime_average' => '150000.00'],
                                'pending_days' => ['vacations' => 15, 'salary' => 15],
                                'liquidations' => [],
                            ],
                            'variants' => [
                                ['value' => 'prestacional', 'label' => 'Prestaciones sociales'],
                                ['value' => 'con_indemnizacion', 'label' => 'Prestaciones + indemnización'],
                            ],
                            'suggestedVariant' => 'con_indemnizacion',
                            'manualConcepts' => [
                                ['code' => 'DESC_PRESTAMOS', 'name' => 'Descuento por préstamos', 'side' => 'Deducción'],
                                ['code' => 'DESC_OTRAS', 'name' => 'Otras deducciones', 'side' => 'Deducción'],
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'El contrato no admite liquidación (estado inválido o sin permisos).'),
        ],
    )]
    public function create(): void {}

    #[OA\Post(
        path: '/contratos/{contract}/liquidar',
        operationId: 'liquidations.store',
        summary: 'Liquidar un contrato (calcula, guarda y audita)',
        tags: ['Liquidaciones'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 7),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LiquidationInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Liquidación guardada: redirección a `/liquidaciones/{liquidation}` con toast `La liquidación {folio} quedó guardada.`.'),
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'El contrato no admite liquidación.'),
        ],
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/liquidaciones/{liquidation}',
        operationId: 'liquidations.show',
        summary: 'Detalle del documento de liquidación',
        tags: ['Liquidaciones'],
        parameters: [
            new OA\Parameter(name: 'liquidation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 12),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `liquidations/show` con el documento completo (líneas con traza, totales e indemnización).',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'component', type: 'string', example: 'liquidations/show'),
                            new OA\Property(
                                property: 'props',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'liquidation', ref: '#/components/schemas/LiquidationDocument'),
                                    new OA\Property(property: 'contract', ref: '#/components/schemas/ContractCard'),
                                ],
                            ),
                        ],
                    ),
                    example: [
                        'component' => 'liquidations/show',
                        'props' => [
                            'liquidation' => [
                                'id' => 12,
                                'folio' => 'LIQ-2024-00012',
                                'status' => ['value' => 'guardada', 'label' => 'Guardada'],
                                'variant' => ['value' => 'con_indemnizacion', 'label' => 'Con indemnización'],
                                'observations' => null,
                                'creator' => 'Administrador Blindem',
                                'dates' => ['settlement' => '2024-06-30', 'period_from' => '2021-03-15', 'period_to' => '2024-06-30'],
                                'bases' => ['prestacional' => '2150000.00', 'vacacional' => '1900905.00', 'smlmv' => '1300000.00'],
                                'days' => ['cesantias' => 180, 'prima' => 180, 'vacaciones' => 15, 'salario_pendiente' => 15, 'worked' => 1204],
                                'lines' => [
                                    ['code' => 'CESANTIAS', 'name' => 'Cesantías', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '2150000', 'quantity' => '180', 'divisor' => 360, 'formula' => 'Base prestacional × Días / 360', 'trace' => '2150000 × 180 / 360 = 1075000', 'amount' => '1075000.00', 'accrues_social_security' => true],
                                    ['code' => 'INT_CESANTIAS', 'name' => 'Intereses de cesantías', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '1075000', 'quantity' => '180', 'divisor' => 360, 'formula' => 'Cesantías × Días × 12% / 360', 'trace' => '1075000 × 180 × 0.12 / 360 = 64500', 'amount' => '64500.00', 'accrues_social_security' => true],
                                    ['code' => 'PRIMA', 'name' => 'Prima de servicios', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '2150000', 'quantity' => '180', 'divisor' => 360, 'formula' => 'Base prestacional × Días / 360', 'trace' => '2150000 × 180 / 360 = 1075000', 'amount' => '1075000.00', 'accrues_social_security' => true],
                                    ['code' => 'VACACIONES', 'name' => 'Vacaciones', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '1900905', 'quantity' => '15', 'divisor' => 720, 'formula' => 'Base vacacional × Días / 720', 'trace' => '1900905 × 15 / 720 = 39602.1875', 'amount' => '39602.00', 'accrues_social_security' => true],
                                    ['code' => 'IND_SIN_JUSTA_CAUSA', 'name' => 'Indemnización por despido sin justa causa', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '1750905', 'quantity' => '75.972602739726', 'divisor' => 30, 'formula' => '(Salario Básico × Días Indemnizables) / 30', 'trace' => '1750905 × 75.972602739726 / 30 = 4434027', 'amount' => '4434027.00', 'accrues_social_security' => false],
                                    ['code' => 'SALARIO_PENDIENTE', 'name' => 'Salario pendiente', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '1750905', 'quantity' => '15', 'divisor' => 30, 'formula' => 'Salario × Días / 30', 'trace' => '58363.5 × 15 = 875453', 'amount' => '875453.00', 'accrues_social_security' => true],
                                    ['code' => 'AUX_TRANSPORTE', 'name' => 'Auxilio de transporte', 'side' => ['value' => 'devengado', 'label' => 'Devengado'], 'base' => '249095', 'quantity' => '15', 'divisor' => 30, 'formula' => 'Auxilio × Días / 30', 'trace' => '249095 / 30 × 15 = 124548', 'amount' => '124548.00', 'accrues_social_security' => true],
                                    ['code' => 'SALUD', 'name' => 'Aporte a salud 4%', 'side' => ['value' => 'deduccion', 'label' => 'Deducción'], 'base' => '915055', 'quantity' => '0.04', 'divisor' => null, 'formula' => 'Base aportes × 4%', 'trace' => '915055 × 0.04 = 36602.2', 'amount' => '36602.00', 'accrues_social_security' => false],
                                    ['code' => 'PENSION', 'name' => 'Aporte a pensión 4%', 'side' => ['value' => 'deduccion', 'label' => 'Deducción'], 'base' => '915055', 'quantity' => '0.04', 'divisor' => null, 'formula' => 'Base aportes × 4%', 'trace' => '915055 × 0.04 = 36602.2', 'amount' => '36602.00', 'accrues_social_security' => false],
                                ],
                                'totals' => ['earned' => '7688130.00', 'deducted' => '73204.00', 'net' => '7614926.00'],
                                'notices' => [],
                                'indemnization' => [
                                    'regla' => 'Salario menor a 10 SMLMV — literal A, artículo 64 CST',
                                    'anos_completos' => 3,
                                    'fraccion' => 109,
                                    'dias_del_ano' => 365,
                                    'dias_indemnizables' => '75.972602739726',
                                ],
                                'tenure' => ['anos_completos' => 3, 'fraccion' => 109, 'dias_del_ano' => 365],
                                'wages' => ['smlmv' => '1300000.00', 'auxilio' => '162350.00'],
                                'parameters' => ['cesantias_divisor' => 360, 'vacaciones_divisor' => 720, 'prima_divisor' => 360, 'aportes_pct' => '0.04'],
                                'captured_at' => '2024-06-30T10:15:20-05:00',
                            ],
                            'contract' => [
                                'id' => 7,
                                'code' => 'CT-2024-001',
                                'worker' => ['id' => 3, 'name' => 'Juan Carlos Pérez'],
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '404', description: 'Liquidación inexistente.'),
            new OA\Response(response: '403', description: 'Sin permiso para ver la liquidación.'),
        ],
    )]
    public function show(): void {}

    #[OA\Get(
        path: '/liquidaciones/{liquidation}/excel',
        operationId: 'liquidations.excel',
        summary: 'Exportar la liquidación a Excel (formato estándar)',
        tags: ['Liquidaciones'],
        parameters: [
            new OA\Parameter(name: 'liquidation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 12),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Libro `.xlsx` (PhpSpreadsheet) del formato estándar de liquidación e indemnización, fiel al Excel del cliente. El archivo se envía con `Content-Disposition: attachment; filename=…` y el temporal se borra después del envío.',
                content: [
                    new OA\MediaType(
                        mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ),
                ],
            ),
            new OA\Response(response: '403', description: 'Sin permiso para ver la liquidación.'),
            new OA\Response(response: '404', description: 'Liquidación inexistente.'),
        ],
    )]
    public function excel(): void {}
}
