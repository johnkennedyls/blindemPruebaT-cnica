<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Endpoints generales: raíz, sesión Fortify, passkeys y panel.
 */
final class GeneralPaths
{
    #[OA\Get(
        path: '/',
        operationId: 'home.redirect',
        summary: 'Raíz de la aplicación',
        tags: ['General'],
        security: [],
        responses: [
            new OA\Response(
                response: '302',
                description: 'Redirige a `/login` si no hay sesión y a `/dashboard` si el usuario ya entró.',
                headers: [
                    new OA\Header(header: 'Location', schema: new OA\Schema(type: 'string'), example: 'http://127.0.0.1:8000/login'),
                ],
            ),
        ],
    )]
    public function home(): void {}

    #[OA\Get(
        path: '/login',
        operationId: 'auth.login.page',
        summary: 'Página de inicio de sesión (Fortify)',
        tags: ['General'],
        security: [],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Formulario de acceso y cookies de sesión frescas (incluye `laravel_session` y `XSRF-TOKEN`).',
                content: [
                    new OA\MediaType(mediaType: 'text/html', example: '<!doctype html>…'),
                    new OA\MediaType(
                        mediaType: 'application/json',
                        example: ['component' => 'auth/login', 'props' => []],
                    ),
                ],
            ),
        ],
    )]
    public function loginPage(): void {}

    #[OA\Post(
        path: '/login',
        operationId: 'auth.login.store',
        summary: 'Iniciar sesión por usuario/contraseña',
        tags: ['General'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', format: 'email', example: 'admin@blindem.test'),
                    new OA\Property(property: 'password', format: 'password', example: 'password'),
                    new OA\Property(property: 'remember', type: 'boolean', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: '302',
                description: 'Sesión creada: redirección a `/dashboard`.',
            ),
            new OA\Response(
                response: '422',
                description: 'Credenciales inválidas (formato de errores de Laravel).',
            ),
            new OA\Response(
                response: '419',
                description: 'Token CSRF expirado: refrescar `/login` y reintentar.',
            ),
        ],
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/logout',
        operationId: 'auth.logout.store',
        summary: 'Cerrar la sesión vigente',
        tags: ['General'],
        responses: [
            new OA\Response(response: '302', description: 'Sesión destruida: redirección a `/`.'),
        ],
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/.well-known/passkey-endpoints',
        operationId: 'wellknown.passkeys',
        summary: 'Enlaces de gestión de passkeys',
        tags: ['General'],
        security: [],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Enlaces estándar de passkeys.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'enroll', type: 'string', example: 'http://127.0.0.1:8000/settings/security'),
                        new OA\Property(property: 'manage', type: 'string', example: 'http://127.0.0.1:8000/settings/security'),
                    ],
                ),
            ),
        ],
    )]
    public function passkeys(): void {}

    #[OA\Get(
        path: '/dashboard',
        operationId: 'dashboard.page',
        summary: 'Dashboard de la sesión autenticada',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Panel Inertia (`dashboard`) con el conteo de contratos que el usuario en sesión puede ver y con los cinco retiros más antiguos sin liquidar.',
                content: [
                    new OA\MediaType(mediaType: 'text/html', example: '<!doctype html>…'),
                    new OA\MediaType(
                        mediaType: 'application/json',
                        example: [
                            'component' => 'dashboard',
                            'props' => [
                                'name' => 'Blindem Liquidaciones',
                                'auth' => [
                                    'user' => ['id' => 1, 'name' => 'Administrador Blindem', 'email' => 'admin@blindem.test'],
                                    'is_admin' => true,
                                ],
                                'summary' => [
                                    'active' => 3,
                                    'liquidatable' => 1,
                                    'liquidated' => 1,
                                    'liquidations' => 2,
                                ],
                                'pending' => [['id' => 5, 'code' => 'CTR-000005', 'can_liquidate' => true]],
                            ],
                        ],
                    ),
                ],
            ),
        ],
    )]
    public function dashboard(): void {}
}
