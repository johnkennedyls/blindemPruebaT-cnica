<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Esquemas de seguridad y respuestas reutilizables de la documentación.
 */
#[OA\SecurityScheme(
    securityScheme: 'WebSession',
    type: 'apiKey',
    in: 'cookie',
    name: 'laravel_session',
    description: 'Sesión web de Laravel/Fortify vía cookie. Establezca la sesión con `POST /login` para habilitar los endpoints protegidos.',
)]
#[OA\SecurityScheme(
    securityScheme: 'CsrfHeader',
    type: 'apiKey',
    in: 'header',
    name: 'X-CSRF-TOKEN',
    description: 'Token CSRF requerido por Laravel en cualquier petición de escritura. Tómelo de la cookie `XSRF-TOKEN` (url-decode) o del `<meta name="csrf-token">` de la página servida.',
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Errores de validación en el formato de Laravel (HTTP 422).',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'La petición no se guardó por errores de validación.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
                example: ['code' => ['Ya existe un contrato registrado con ese código.']],
            ),
        ],
    ),
)]
#[OA\Response(
    response: 'CsrfExpired',
    description: 'El token CSRF expiró o no se envió (HTTP 419). La respuesta devuelve la vista del estado Inertia actual y el cliente refresca el token.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'CSRF token mismatch.'),
        ],
    ),
)]
#[OA\Response(
    response: 'Unauthenticated',
    description: 'La sesión no está establecida: el servidor redirige al login (HTTP 302 hacia /login).',
    content: new OA\MediaType(mediaType: 'text/html', example: 'Redirección de Fortify hacia `/login`.'),
)]
final class SecurityDefinitions
{
    public function __construct() {}
}
