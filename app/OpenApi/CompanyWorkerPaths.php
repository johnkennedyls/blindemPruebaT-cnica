<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Empresas y trabajadores (consulta + CRUD administrador).
 */
final class CompanyWorkerPaths
{
    #[OA\Get(
        path: '/empresas',
        operationId: 'companies.index',
        summary: 'Listado de empresas',
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string'),
                description: 'Búsqueda por razón social o NIT.'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `companies/index` con tarjetas de empresa paginadas.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'companies/index',
                        'props' => [
                            'companies' => [
                                'current_page' => 1,
                                'data' => [
                                    [
                                        'id' => 1,
                                        'nit' => '900123456-7',
                                        'business_name' => 'Blindem S.A.S.',
                                        'trade_name' => 'Blindem',
                                        'activity_code' => '6201',
                                        'address' => 'Calle 12 #34-56',
                                        'city' => 'Bogotá',
                                        'phone' => '6011234567',
                                        'email' => 'rrhh@blindem.test',
                                        'legal_rep_name' => 'Ana Restrepo',
                                        'legal_rep_document' => '52894641',
                                        'is_active' => true,
                                        'contracts_count' => 2,
                                    ],
                                ],
                                'total' => 1,
                            ],
                            'filters' => ['q' => null],
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function companiesIndex(): void {}

    #[OA\Get(
        path: '/empresas/crear',
        operationId: 'companies.create',
        summary: 'Formulario de creación de empresa (administrador)',
        tags: ['Empresas'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `companies/form` con `company: null`.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'companies/form',
                        'props' => ['company' => null],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function companiesCreate(): void {}

    #[OA\Post(
        path: '/empresas',
        operationId: 'companies.store',
        summary: 'Registrar empresa (administrador)',
        tags: ['Empresas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CompanyInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Guardado: redirección a `/empresas/{company}/editar` con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function companiesStore(): void {}

    #[OA\Get(
        path: '/empresas/{company}/editar',
        operationId: 'companies.edit',
        summary: 'Formulario de edición de empresa (administrador)',
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `companies/form` precargada con la tarjeta de la empresa.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'companies/form',
                        'props' => [
                            'company' => [
                                'id' => 1,
                                'nit' => '900123456-7',
                                'business_name' => 'Blindem S.A.S.',
                                'trade_name' => 'Blindem',
                                'activity_code' => '6201',
                                'address' => 'Calle 12 #34-56',
                                'city' => 'Bogotá',
                                'phone' => '6011234567',
                                'email' => 'rrhh@blindem.test',
                                'legal_rep_name' => 'Ana Restrepo',
                                'legal_rep_document' => '52894641',
                                'is_active' => true,
                                'contracts_count' => 2,
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Empresa inexistente.'),
        ],
    )]
    public function companiesEdit(): void {}

    #[OA\Put(
        path: '/empresas/{company}',
        operationId: 'companies.update',
        summary: 'Actualizar empresa (administrador)',
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CompanyInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Actualizado: redirección a la edición con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function companiesUpdate(): void {}

    #[OA\Delete(
        path: '/empresas/{company}',
        operationId: 'companies.destroy',
        summary: 'Eliminar empresa (administrador; bloqueada si tiene contratos)',
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: '302', description: 'Eliminada o bloqueada (toast de error) si tiene contratos registrados.'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Empresa inexistente.'),
        ],
    )]
    public function companiesDestroy(): void {}

    #[OA\Get(
        path: '/trabajadores',
        operationId: 'workers.index',
        summary: 'Listado de trabajadores (filtrado por rol)',
        tags: ['Trabajadores'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string'),
                description: 'Búsqueda por nombre o número de documento.'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `workers/index`: el colaborador sólo ve trabajadores con contratos en sus empresas; el administrador ve todos.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'workers/index',
                        'props' => [
                            'workers' => [
                                'current_page' => 1,
                                'data' => [
                                    [
                                        'id' => 3,
                                        'name' => 'Juan Carlos Pérez Gómez',
                                        'document' => 'CC 1098765432',
                                        'first_names' => 'Juan Carlos',
                                        'last_names' => 'Pérez Gómez',
                                        'document_type_id' => 1,
                                        'document_number' => '1098765432',
                                        'birth_date' => '1990-04-12',
                                        'gender' => 'm',
                                        'email' => 'jperez@blindem.test',
                                        'phone' => '3101234567',
                                        'address' => 'Carrera 4 #21-30',
                                        'city' => 'Bogotá',
                                        'department' => 'Cundinamarca',
                                        'is_active' => true,
                                        'document_type' => 'Cédula de ciudadanía',
                                        'contracts_count' => 1,
                                    ],
                                ],
                                'total' => 1,
                            ],
                            'filters' => ['q' => null],
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function workersIndex(): void {}

    #[OA\Get(
        path: '/trabajadores/crear',
        operationId: 'workers.create',
        summary: 'Formulario de creación de trabajador (administrador)',
        tags: ['Trabajadores'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `workers/form` con tipos de documento vigentes para personas naturales y géneros.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'workers/form',
                        'props' => [
                            'worker' => null,
                            'documentTypes' => [['value' => 1, 'label' => 'CC · Cédula de ciudadanía']],
                            'genders' => [
                                ['value' => 'm', 'label' => 'Masculino'],
                                ['value' => 'f', 'label' => 'Femenino'],
                                ['value' => 'otro', 'label' => 'Otro'],
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function workersCreate(): void {}

    #[OA\Post(
        path: '/trabajadores',
        operationId: 'workers.store',
        summary: 'Registrar trabajador (administrador)',
        tags: ['Trabajadores'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WorkerInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Guardado: redirección a `/trabajadores/{worker}/editar` con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function workersStore(): void {}

    #[OA\Get(
        path: '/trabajadores/{worker}/editar',
        operationId: 'workers.edit',
        summary: 'Formulario de edición de trabajador (administrador)',
        tags: ['Trabajadores'],
        parameters: [
            new OA\Parameter(name: 'worker', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 3),
        ],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `workers/form` precargada con la tarjeta del trabajador y conteo de contratos.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'workers/form',
                        'props' => [
                            'worker' => [
                                'id' => 3,
                                'name' => 'Juan Carlos Pérez Gómez',
                                'document' => 'CC 1098765432',
                                'first_names' => 'Juan Carlos',
                                'last_names' => 'Pérez Gómez',
                                'document_type_id' => 1,
                                'document_number' => '1098765432',
                                'birth_date' => '1990-04-12',
                                'gender' => 'm',
                                'email' => 'jperez@blindem.test',
                                'phone' => '3101234567',
                                'address' => 'Carrera 4 #21-30',
                                'city' => 'Bogotá',
                                'department' => 'Cundinamarca',
                                'is_active' => true,
                                'document_type' => 'Cédula de ciudadanía',
                                'contracts_count' => 1,
                            ],
                            'documentTypes' => [['value' => 1, 'label' => 'CC · Cédula de ciudadanía']],
                            'genders' => [
                                ['value' => 'm', 'label' => 'Masculino'],
                                ['value' => 'f', 'label' => 'Femenino'],
                                ['value' => 'otro', 'label' => 'Otro'],
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Trabajador inexistente.'),
        ],
    )]
    public function workersEdit(): void {}

    #[OA\Put(
        path: '/trabajadores/{worker}',
        operationId: 'workers.update',
        summary: 'Actualizar trabajador (administrador)',
        tags: ['Trabajadores'],
        parameters: [
            new OA\Parameter(name: 'worker', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 3),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WorkerInput'),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Actualizado: redirección a la edición con toast de éxito.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
        ],
    )]
    public function workersUpdate(): void {}

    #[OA\Delete(
        path: '/trabajadores/{worker}',
        operationId: 'workers.destroy',
        summary: 'Eliminar trabajador (administrador; bloqueado si tiene contratos)',
        tags: ['Trabajadores'],
        parameters: [
            new OA\Parameter(name: 'worker', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 3),
        ],
        responses: [
            new OA\Response(response: '302', description: 'Eliminado o bloqueado (toast de error) si tiene contratos registrados.'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
            new OA\Response(response: '403', description: 'Sólo administrador.'),
            new OA\Response(response: '404', description: 'Trabajador inexistente.'),
        ],
    )]
    public function workersDestroy(): void {}
}
