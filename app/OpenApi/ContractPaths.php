<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * CRUD de contratos (`routes/web.php::contracts.*`).
 */
final class ContractPaths
{
    #[OA\Get(
        path: '/contratos',
        operationId: 'contracts.index',
        summary: 'Listado de contratos con filtros',
        tags: ['Contratos'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['activo', 'terminado', 'en_liquidacion', 'liquidado']),
                description: 'Filtro por estado del contrato.'),
            new OA\Parameter(name: 'empresa', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'Filtro por empresa (el colaborador sólo ve sus empresas).'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `contracts/index` con el paginador de tarjetas de contrato y las listas de opciones de los filtros.',
                content: [
                    new OA\MediaType(mediaType: 'text/html', example: '<!doctype html>…'),
                    new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            properties: [
                                new OA\Property(property: 'component', type: 'string', example: 'contracts/index'),
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
                                                new OA\Property(property: 'per_page', type: 'integer'),
                                                new OA\Property(property: 'last_page', type: 'integer'),
                                            ],
                                        ),
                                        new OA\Property(property: 'filters', type: 'object',
                                            properties: [new OA\Property(property: 'status', type: 'string', nullable: true)]),
                                        new OA\Property(property: 'statuses', type: 'array', items: new OA\Items(ref: '#/components/schemas/LabeledValue')),
                                    ],
                                ),
                            ],
                        ),
                        example: [
                            'component' => 'contracts/index',
                            'props' => [
                                'contracts' => [
                                    'current_page' => 1,
                                    'data' => [
                                        [
                                            'id' => 7,
                                            'code' => 'CT-2024-001',
                                            'status' => ['value' => 'terminado', 'label' => 'Terminado'],
                                            'can_liquidate' => true,
                                            'worker' => [
                                                'id' => 3,
                                                'name' => 'Juan Carlos Pérez',
                                                'document' => 'CC 1098765432',
                                                'document_type_id' => 1,
                                                'document_number' => '1098765432',
                                                'email' => null,
                                            ],
                                            'company' => 'Blindem S.A.S.',
                                            'position' => 'Analista Operativo',
                                            'contract_type' => 'Indefinido',
                                            'modality' => 'Indefinido',
                                            'termination_reason' => 'Despido sin justa causa',
                                            'applies_indemnization' => true,
                                            'dates' => ['start' => '2023-01-15', 'end' => '2024-06-30', 'scheduled_end' => null],
                                            'salary' => ['basic' => '1750905.00', 'auxiliary' => '249095.00', 'overtime_average' => '150000.00'],
                                            'pending_days' => ['vacations' => 15, 'salary' => 15],
                                            'liquidations' => [],
                                        ],
                                    ],
                                    'from' => 1,
                                    'last_page' => 1,
                                    'per_page' => 10,
                                    'to' => 1,
                                    'total' => 1,
                                ],
                                'filters' => ['status' => null],
                                'statuses' => [
                                    ['value' => 'activo', 'label' => 'Activo'],
                                    ['value' => 'terminado', 'label' => 'Terminado'],
                                    ['value' => 'en_liquidacion', 'label' => 'En liquidación'],
                                    ['value' => 'liquidado', 'label' => 'Liquidado'],
                                ],
                            ],
                        ],
                    ),
                ],
            ),
        ],
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/contratos/crear',
        operationId: 'contracts.create',
        summary: 'Formulario de creación de contrato',
        tags: ['Contratos'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `contracts/form` con catálogos para los selects (trabajadores visibles, empresas del rol, tipos de contrato, cargos y motivos de retiro).',
                content: [
                    new OA\MediaType(mediaType: 'text/html', example: '<!doctype html>…'),
                    new OA\MediaType(
                        mediaType: 'application/json',
                        example: [
                            'component' => 'contracts/form',
                            'props' => [
                                'contract' => null,
                                'workers' => [['value' => 3, 'label' => 'Juan Carlos Pérez · CC 1098765432']],
                                'companies' => [['value' => 1, 'label' => 'Blindem S.A.S.']],
                                'contractTypes' => [['value' => 1, 'label' => 'Indefinido']],
                                'positions' => [['value' => 2, 'label' => 'Analista Operativo']],
                                'terminationReasons' => [['value' => 2, 'label' => 'Despido sin justa causa']],
                                'transportAuxiliaryMode' => [['value' => 'auto', 'label' => 'Automático (SMLMV vigente)']],
                                'statuses' => [['value' => 'activo', 'label' => 'Activo']],
                            ],
                        ],
                    ),
                ],
            ),
        ],
    )]
    public function create(): void {}

    #[OA\Post(
        path: '/contratos',
        operationId: 'contracts.store',
        summary: 'Registrar un contrato',
        tags: ['Contratos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ContractInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Guardado: redirección a `/contratos/{contract}/editar` con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/contratos/{contract}/editar',
        operationId: 'contracts.edit',
        summary: 'Formulario de edición de contrato',
        tags: ['Contratos'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 7),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `contracts/form` precargada con `Formulario de contrato` (fuente de la edición).',
                content: [
                    new OA\MediaType(mediaType: 'text/html', example: '<!doctype html>…'),
                    new OA\MediaType(
                        mediaType: 'application/json',
                        example: [
                            'component' => 'contracts/form',
                            'props' => [
                                'contract' => [
                                    'id' => 7,
                                    'code' => 'CT-2024-001',
                                    'worker_id' => 3,
                                    'worker_name' => 'Juan Carlos Pérez',
                                    'company_id' => 1,
                                    'contract_type_id' => 1,
                                    'position_id' => 2,
                                    'termination_reason_id' => 2,
                                    'creator' => 'Administrador Blindem',
                                    'dates' => ['start' => '2021-03-15', 'end' => '2024-06-30', 'scheduled_end' => null, 'salary_effective_from' => null],
                                    'base_salary_monthly' => '1750905.00',
                                    'transport_auxiliary_mode' => 'manual',
                                    'transport_auxiliary_monthly' => '249095.00',
                                    'overtime_average_monthly' => '150000.00',
                                    'status' => 'terminado',
                                    'pending_salary_days' => 15,
                                    'pending_vacation_days' => 15,
                                    'notes' => null,
                                    'liquidations_count' => 0,
                                ],
                                'workers' => [['value' => 3, 'label' => 'Juan Carlos Pérez · CC 1098765432']],
                                'companies' => [['value' => 1, 'label' => 'Blindem S.A.S.']],
                                'contractTypes' => [['value' => 1, 'label' => 'Indefinido']],
                                'positions' => [['value' => 2, 'label' => 'Analista Operativo']],
                                'terminationReasons' => [['value' => 2, 'label' => 'Despido sin justa causa']],
                                'transportAuxiliaryMode' => [['value' => 'auto', 'label' => 'Automático (SMLMV vigente)']],
                                'statuses' => [['value' => 'activo', 'label' => 'Activo']],
                            ],
                        ],
                    ),
                ],
            ),
            new OA\Response(response: '404', description: 'Contrato inexistente.'),
            new OA\Response(response: '403', description: 'El colaborador no accede a contratos fuera de sus empresas.'),
        ],
    )]
    public function edit(): void {}

    #[OA\Put(
        path: '/contratos/{contract}',
        operationId: 'contracts.update',
        summary: 'Actualizar un contrato',
        tags: ['Contratos'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 7),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ContractInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Actualizado: redirección a la edición con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/contratos/{contract}',
        operationId: 'contracts.destroy',
        summary: 'Eliminar un contrato',
        tags: ['Contratos'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 7),
        ],
        responses: [
            new OA\Response(response: '302', description: 'Eliminado (o bloqueado si tiene liquidaciones): vuelve al listado con toast de éxito o error.'),
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '404', description: 'Contrato inexistente.'),
        ],
    )]
    public function destroy(): void {}
}
