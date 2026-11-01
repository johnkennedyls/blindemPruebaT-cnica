<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Punto de ensamblaje de la especificación OpenAPI de Blindem.
 *
 * swagger-php recolecta todas las anotaciones OA presentes en app/OpenApi/;
 * este archivo aporta la cabecera general (info, servidores, tags, seguridad).
 */
#[OA\OpenApi(
    security: [
        ['WebSession' => [], 'CsrfHeader' => []],
    ],
)]
#[OA\Info(
    version: '1.0.0',
    title: 'Blindem — Liquidación de Prestaciones e Indemnización Laboral',
    description: 'Documentación operativa de **toda la aplicación web Blindem**, un sistema Laravel 13 + React 19 (Inertia) que liquida prestaciones sociales, vacaciones, indemnización por despido sin justa causa y genera el formato estándar de liquidación en Excel, coincidiendo al número cerrado con `Formato_Liquidacion_e_Indemnizacion_Prueba Blindem.xlsx`.'.
        "\n\n".
        '**Arquitectura**'.
        "\n\n".
        '- **Blindem es una SPA Inertia**: los endpoints `GET` entregan la página HTML que React monta con el payload de `props` (componente Inertia). Con el header `X-Inertia` el mismo endpoint responde JSON `{ "component": "…", "props": {…} }`; ambos formatos están documentados en cada endpoint.'.
        "\n".
        '- **Autenticación**: sesión web por cookies (Fortify). Las peticiones de escritura exigen un token CSRF válido en el header `X-CSRF-TOKEN` (la SPA lo obtiene de la cookie `XSRF-TOKEN` / del `<meta name="csrf-token">`). Si el token venció, el servidor responde `419`.'.
        "\n".
        '- **Roles**: `administrator` (todo, incluidos catálogos paramétricos y CRUD de empresa/trabajador) y `collaborator` (consulta de contratos por empresa y registro/edición de contratos).'.
        "\n".
        '- **Calculadora**: la liquidación reproduce el Excel del cliente al peso cerrado; los 12 valores del Formato A y el neto del Formato B están fijados en pruebas automatizadas (`tests/Feature/Calculation/LiquidacionConformeAlExcelTest.php`).'.
        "\n\n".
        '**Cómo probar desde Swagger UI**'.
        "\n\n".
        '1. Inicie sesión en la aplicación (por ejemplo desde `/login`) para que el navegador obtenga las cookies de sesión.'.
        "\n".
        '2. Habilite la autorización "🔐 CsrfHeader" y pegue el token CSRF vigente.'.
        "\n".
        '3. Ejecute cualquier operación: las cookies de sesión viajan en la misma petición y el servidor devuelve el HTML de la página o el JSON de la acción.',
    contact: new OA\Contact(
        name: 'Blindem — Prueba técnica',
        email: 'ingenieria@blindem.test',
    ),
)]
#[OA\Server(url: '/', description: 'Servidor actual (relativo al host que sirve la UI)')]
#[OA\Server(url: 'http://127.0.0.1:8000', description: '`php artisan serve` local')]
#[OA\Tag(name: 'General', description: 'Página raíz pública, login/logout y passkeys')]
#[OA\Tag(name: 'Dashboard', description: 'Home de la sesión autenticada')]
#[OA\Tag(name: 'Contratos', description: 'CRUD de contratos laborales')]
#[OA\Tag(name: 'Liquidaciones', description: 'Liquidación de prestaciones/indemnización + exportación Excel')]
#[OA\Tag(name: 'Empresas', description: 'Consulta pública / CRUD de empresas (secciones de administración)')]
#[OA\Tag(name: 'Trabajadores', description: 'Consulta pública / CRUD de trabajadores (secciones de administración)')]
#[OA\Tag(name: 'Paramétricas', description: 'Catálogos paramétricos genéricos (administrador)')]
#[OA\Tag(name: 'Configuración de la cuenta', description: 'Perfil, contraseña, baja de la cuenta y apariencia')]
final class DocumentationSpec
{
    public function __construct() {}
}
