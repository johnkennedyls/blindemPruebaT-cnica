<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo de empresa (ver `App\Http\Requests\CompanyRequest`).
 */
#[OA\Schema(
    schema: 'CompanyInput',
    title: 'Cuerpo de empresa',
    description: 'Validación de `POST /empresas` y `PUT /empresas/{company}` (administrador). El NIT admite dígito-verificador separado por guion y es único.',
    required: ['nit', 'business_name'],
    properties: [
        new OA\Property(property: 'nit', type: 'string', minLength: 8, maxLength: 15, pattern: '^[0-9]+(-?[0-9]+)?$', example: '900123456-7'),
        new OA\Property(property: 'business_name', type: 'string', maxLength: 200, example: 'Blindem S.A.S.'),
        new OA\Property(property: 'trade_name', type: 'string', maxLength: 200, nullable: true),
        new OA\Property(property: 'activity_code', type: 'string', maxLength: 20, nullable: true,
            description: 'Código de actividad económica (CIIU).'),
        new OA\Property(property: 'address', type: 'string', maxLength: 250, nullable: true),
        new OA\Property(property: 'city', type: 'string', maxLength: 60, nullable: true),
        new OA\Property(property: 'phone', type: 'string', maxLength: 30, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 160, nullable: true),
        new OA\Property(property: 'legal_rep_name', type: 'string', maxLength: 200, nullable: true,
            description: 'Nombre del representante legal.'),
        new OA\Property(property: 'legal_rep_document', type: 'string', maxLength: 30, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
final class CompanyInput {}
