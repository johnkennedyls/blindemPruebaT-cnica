<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo de trabajador (ver `App\Http\Requests\WorkerRequest`).
 */
#[OA\Schema(
    schema: 'WorkerInput',
    title: 'Cuerpo de trabajador',
    description: 'Validación de `POST /trabajadores` y `PUT /trabajadores/{worker}` (administrador). El documento es único por combinación tipo+número.',
    required: ['document_type_id', 'document_number', 'first_names', 'last_names'],
    properties: [
        new OA\Property(property: 'document_type_id', type: 'integer', example: 1,
            description: 'Id del catálogo `tipos-de-documento` vigente para personas naturales.'),
        new OA\Property(property: 'document_number', type: 'string', maxLength: 30, pattern: '^[0-9]+([.\-]?[0-9]+)*$', example: '1098765432'),
        new OA\Property(property: 'first_names', type: 'string', maxLength: 120, example: 'Juan Carlos'),
        new OA\Property(property: 'last_names', type: 'string', maxLength: 120, example: 'Pérez Gómez'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true,
            description: 'No puede ser futura.'),
        new OA\Property(property: 'gender', type: 'string', enum: ['m', 'f', 'otro'], nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 160, nullable: true),
        new OA\Property(property: 'phone', type: 'string', maxLength: 30, nullable: true),
        new OA\Property(property: 'address', type: 'string', maxLength: 250, nullable: true),
        new OA\Property(property: 'city', type: 'string', maxLength: 60, nullable: true),
        new OA\Property(property: 'department', type: 'string', maxLength: 60, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
final class WorkerInput {}
