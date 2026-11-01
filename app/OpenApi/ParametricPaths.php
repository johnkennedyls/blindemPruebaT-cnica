<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * CRUD genérico de catálogos paramétricos (administrador).
 */
final class ParametricPaths
{
    #[OA\Get(
        path: '/parametricas/{resource}',
        operationId: 'parametrics.index',
        summary: 'Listado paginado del catálogo',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `parametrics/index` con la definición del catálogo (campos, opciones de selects), filas paginadas y el filtro de búsqueda.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'component', type: 'string', example: 'parametrics/index'),
                            new OA\Property(property: 'definition', type: 'object'),
                            new OA\Property(
                                property: 'rows',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'current_page', type: 'integer'),
                                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ParametricRow')),
                                    new OA\Property(property: 'total', type: 'integer'),
                                ],
                            ),
                            new OA\Property(property: 'filters', type: 'object'),
                        ],
                    ),
                    example: [
                        'component' => 'parametrics/index',
                        'props' => [
                            'definition' => [
                                'slug' => 'tipos-de-documento',
                                'title' => 'Tipos de documento',
                                'one' => 'tipo de documento',
                                'description' => 'Catálogo que identifica a trabajadores y representantes legales.',
                                'headline' => ['attribute' => 'code', 'label' => 'Código'],
                                'fields' => [
                                    ['name' => 'code', 'label' => 'Código', 'kind' => 'text', 'required' => true, 'hint' => 'Ejemplo: CC, CE, TI, NIT.'],
                                    ['name' => 'name', 'label' => 'Nombre', 'kind' => 'text', 'required' => true],
                                    ['name' => 'abbreviation', 'label' => 'Abreviatura', 'kind' => 'text', 'hint' => 'Cómo se muestra junto al número.'],
                                    ['name' => 'holder_type', 'label' => 'Tipo de titular', 'kind' => 'select', 'required' => true, 'options' => [
                                        ['value' => 'natural', 'label' => 'Persona natural'],
                                        ['value' => 'juridica', 'label' => 'Persona jurídica'],
                                        ['value' => 'both', 'label' => 'Naturales y jurídicas'],
                                    ]],
                                ],
                                'usageLabel' => 'trabajador',
                            ],
                            'rows' => [
                                'current_page' => 1,
                                'data' => [
                                    ['id' => 1, 'headline' => 'CC', 'primary' => 'Cédula de ciudadanía', 'secondary' => 'CC 123', 'is_active' => true, 'usage_count' => 3],
                                ],
                                'total' => 1,
                            ],
                            'filters' => ['q' => null],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '404', description: 'Catálogo inexistente en el registro paramétrico.',
                content: new OA\JsonContent(example: ['message' => 'Registro paramétrico no resuelto.'])),
        ],
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/parametricas/{resource}/crear',
        operationId: 'parametrics.create',
        summary: 'Formulario de creación en el catálogo',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `parametrics/form` con la definición del catálogo y `row: null`.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'parametrics/form',
                        'props' => ['definition' => ['slug' => 'cargos', 'title' => 'Cargos'], 'row' => null],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Catálogo inexistente.'),
        ],
    )]
    public function create(): void {}

    #[OA\Post(
        path: '/parametricas/{resource}',
        operationId: 'parametrics.store',
        summary: 'Crear registro en el catálogo',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Campos según la definición del catálogo: 
- `tipos-de-documento`: code*, name*, abbreviation, holder_type* (natural|juridica|both)
- `cargos`: code*, name*, area, level
- `tipos-de-contrato`: code*, name*, modality* (indefinido|termino_fijo|obra_o_labor, según `ContractModality`), statutory_max_months, requires_scheduled_end (bool), indemnization_rule*
- `motivos-de-retiro`: code*, description*, classification*, indemnization_rule*, applies_sanction_art65 (bool)
- `salarios-minimos`: year* (0-2100), smlmv* (0-99999999), transport_auxiliary* (0-99999999), source
`*` campo obligatorio según la validación generada desde la definición.',
            content: new OA\JsonContent(
                example: [
                    'code' => 'CE',
                    'name' => 'Cédula de extranjería',
                    'abbreviation' => 'CE',
                    'holder_type' => 'natural',
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Guardado: redirección a `/parametricas/{resource}/{parametric}/editar` con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/parametricas/{resource}/{parametric}/editar',
        operationId: 'parametrics.edit',
        summary: 'Formulario de edición del registro',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
            new OA\Parameter(name: 'parametric', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `parametrics/form` con la fila normalizada por tipos (`values` por campo del catálogo).',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'parametrics/form',
                        'props' => [
                            'definition' => ['slug' => 'salarios-minimos', 'title' => 'Salarios mínimos'],
                            'row' => [
                                'id' => 1,
                                'headline' => '2021',
                                'values' => ['year' => '2021', 'smlmv' => '1300000', 'transport_auxiliary' => '162350', 'source' => 'Decreto 1404 de 2020'],
                                'is_active' => false,
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Registro inexistente.'),
        ],
    )]
    public function edit(): void {}

    #[OA\Put(
        path: '/parametricas/{resource}/{parametric}',
        operationId: 'parametrics.update',
        summary: 'Actualizar registro del catálogo',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
            new OA\Parameter(name: 'parametric', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'year' => 2021,
                    'smlmv' => 1300000,
                    'transport_auxiliary' => 162350,
                    'source' => 'Decreto 1404 de 2020 (corregido)',
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Actualizado: redirección a la edición con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/parametricas/{resource}/{parametric}',
        operationId: 'parametrics.destroy',
        summary: 'Eliminar registro del catálogo (bloqueado si está en uso)',
        tags: ['Paramétricas'],
        parameters: [
            new OA\Parameter(name: 'resource', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', enum: ['tipos-de-documento', 'cargos', 'tipos-de-contrato', 'motivos-de-retiro', 'salarios-minimos'])),
            new OA\Parameter(name: 'parametric', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: '302', description: 'Eliminado (o `back()` con toast de error si hay registros que lo usan; en ese caso el catálogo sugiere desactivarlo).'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Registro inexistente.'),
        ],
    )]
    public function destroy(): void {}
}
