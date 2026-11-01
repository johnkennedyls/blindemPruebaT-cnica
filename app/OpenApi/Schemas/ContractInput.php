<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Cuerpo de creación/actualización de contratos (ver `App\Http\Requests\ContractRequest`).
 */
#[OA\Schema(
    schema: 'ContractInput',
    title: 'Cuerpo de contrato',
    description: 'Validación de `POST /contratos` y `PUT /contratos/{contract}`. Notas de negocio: un contrato con estado cerrado exige `end_date` y `termination_reason_id`; cada trabajador no puede tener dos contratos solapados; el estado `liquidado` lo fija el sistema y no se admite a mano; los tipos de contrato con `statutory_max_months` limitan `scheduled_end_date` a ese plazo.',
    required: ['code', 'worker_id', 'company_id', 'contract_type_id', 'position_id', 'start_date', 'base_salary_monthly', 'transport_auxiliary_mode', 'status'],
    properties: [
        new OA\Property(property: 'code', type: 'string', minLength: 1, maxLength: 30, pattern: '^[A-Z0-9._\-]+$', example: 'CT-2024-001',
            description: 'Código único del contrato; se normaliza a mayúsculas.'),
        new OA\Property(property: 'worker_id', type: 'integer', example: 1),
        new OA\Property(property: 'company_id', type: 'integer', example: 1,
            description: 'El colaborador sólo puede registrar contratos de empresas en las que colabora.'),
        new OA\Property(property: 'contract_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'position_id', type: 'integer', example: 1),
        new OA\Property(property: 'termination_reason_id', type: 'integer', nullable: true, example: 2,
            description: 'Obligatorio si el estado cierra el contrato.'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2023-01-15'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2024-06-30',
            description: 'Fecha de retiro; debe ser `>= start_date`.'),
        new OA\Property(property: 'scheduled_end_date', type: 'string', format: 'date', nullable: true, example: '2024-01-14',
            description: 'Obligatoria para tipos con `requires_scheduled_end`; no puede superar el plazo legal del tipo.'),
        new OA\Property(property: 'salary_effective_from', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'base_salary_monthly', type: 'string', example: '1750905.00',
            description: 'Salario mensual básico, mayor a cero; se envía en formato decimal.'),
        new OA\Property(property: 'transport_auxiliary_mode', type: 'string', enum: ['auto', 'manual', 'sin_derecho'],
            description: '`auto` toma el auxilio del SMLMV vigente; `sin_derecho` lo fuerza a 0 y gira el auxilio mensual explícito en `manual`.'),
        new OA\Property(property: 'transport_auxiliary_monthly', type: 'string', nullable: true, example: '249095.00',
            description: 'Con `sin_derecho` el servidor lo fija a 0,00.'),
        new OA\Property(property: 'overtime_average_monthly', type: 'string', nullable: true, example: '150000.00',
            description: 'Promedio mensual de horas extras/recargos (base prestacional adicional).'),
        new OA\Property(property: 'status', type: 'string', enum: ['activo', 'terminado', 'en_liquidacion'],
            description: 'El estado `liquidado` lo asigna el sistema al liquidar el contrato.'),
        new OA\Property(property: 'pending_salary_days', type: 'integer', minimum: 0, maximum: 30, nullable: true, example: 15),
        new OA\Property(property: 'pending_vacation_days', type: 'integer', minimum: 0, maximum: 365, nullable: true, example: 15),
        new OA\Property(property: 'notes', type: 'string', maxLength: 2000, nullable: true),
    ],
)]
final class ContractInput {}
