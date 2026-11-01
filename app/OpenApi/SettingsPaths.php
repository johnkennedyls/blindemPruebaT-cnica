<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Configuración de la cuenta (settings.*): perfil, contraseña, 2FA y baja.
 */
final class SettingsPaths
{
    #[OA\Get(
        path: '/settings/profile',
        operationId: 'profile.edit',
        summary: 'Página de perfil',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(
                response: '200',
                description: 'Página `settings/profile` con los datos del usuario en sesión (y estado 2FA si aplica).',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'settings/profile',
                        'props' => [
                            'user' => ['id' => 1, 'name' => 'Administrador Blindem', 'email' => 'admin@blindem.test'],
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function profileEdit(): void {}

    #[OA\Patch(
        path: '/settings/profile',
        operationId: 'profile.update',
        summary: 'Actualizar datos del perfil',
        tags: ['Configuración de la cuenta'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Administrador Blindem'),
                    new OA\Property(property: 'email', format: 'email', example: 'admin@blindem.test'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Perfil actualizado (si el correo cambió se re-verify por `PatchUserEmail`).'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function profileUpdate(): void {}

    #[OA\Delete(
        path: '/settings/profile',
        operationId: 'profile.destroy',
        summary: 'Eliminar la cuenta del usuario en sesión',
        tags: ['Configuración de la cuenta'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'password', format: 'password', example: 'password',
                        description: 'Contraseña actual para confirmar la baja.'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Baja confirmada: sesión cerrada y cuenta eliminada.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function profileDestroy(): void {}

    #[OA\Get(
        path: '/settings/security',
        operationId: 'security.edit',
        summary: 'Página de seguridad (contraseña y seconda-factor)',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: '302', description: 'Los cambios sensibles piden confirmación de contraseña reciente (Fortify `password.confirm`) y redirige a `/user/confirm-password`.'),
            new OA\Response(
                response: '200',
                description: 'Página `settings/security` con factores del usuario y enlaces de passkeys.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: [
                        'component' => 'settings/security',
                        'props' => [
                            'twoFactorEnabled' => false,
                            'confirmsTwoFactor' => false,
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function securityEdit(): void {}

    #[OA\Put(
        path: '/settings/password',
        operationId: 'user-password.update',
        summary: 'Actualizar la contraseña',
        tags: ['Configuración de la cuenta'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', format: 'password', example: 'password'),
                    new OA\Property(property: 'password', format: 'password', example: 'nueva-password'),
                    new OA\Property(property: 'password_confirmation', format: 'password', example: 'nueva-password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Contraseña cambiada.'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function passwordUpdate(): void {}

    #[OA\Get(
        path: '/settings/appearance',
        operationId: 'appearance.edit',
        summary: 'Preferencias de apariencia (tema claro/oscuro)',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Página `settings/appearance`; el tema viaja en la cookie `appearance`.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    example: ['component' => 'settings/appearance', 'props' => []],
                ),
            ),
        ],
    )]
    public function appearanceEdit(): void {}

    #[OA\Get(
        path: '/user/confirm-password',
        operationId: 'password.confirm.page',
        summary: 'Confirmación de contraseña receta (Fortify)',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(response: '200', description: 'Página de confirmación.'),
        ],
    )]
    public function passwordConfirmPage(): void {}

    #[OA\Post(
        path: '/user/confirm-password',
        operationId: 'password.confirm.store',
        summary: 'Confirmar contraseña para operaciones sensibles',
        tags: ['Configuración de la cuenta'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'password', format: 'password', example: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: '302', description: 'Confirmación válida (ventana de rabbitFortify con re-confirmación posterior).'),
            new OA\Response(ref: '#/components/responses/ValidationError'),
            new OA\Response(ref: '#/components/responses/CsrfExpired'),
        ],
    )]
    public function passwordConfirmStore(): void {}

    #[OA\Post(
        path: '/user/two-factor-authentication',
        operationId: 'two-factor.enable',
        summary: 'Activar la autenticación en dos pasos',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(response: '302', description: '2FA habilitado (se comparten los códigos de recuperación).'),
            new OA\Response(response: '419', description: 'Token CSRF expirado.'),
        ],
    )]
    public function twoFactorEnable(): void {}

    #[OA\Delete(
        path: '/user/two-factor-authentication',
        operationId: 'two-factor.disable',
        summary: 'Desactivar la autenticación en dos pasos',
        tags: ['Configuración de la cuenta'],
        responses: [
            new OA\Response(response: '302', description: '2FA deshabilitado.'),
            new OA\Response(response: '419', description: 'Token CSRF expirado.'),
        ],
    )]
    public function twoFactorDisable(): void {}
}
