<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_la_especificacion_openapi_se_sirve_como_json_valido(): void
    {
        $response = $this->get('/documentacion/api/spec.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $spec = $response->json();

        self::assertSame('3.1.0', $spec['openapi']);
        self::assertSame('Blindem — Liquidación de Prestaciones e Indemnización Laboral', $spec['info']['title']);

        // Cobertura de los módulos de la aplicación.
        foreach ([
            '/',
            '/login',
            '/dashboard',
            '/contratos',
            '/contratos/{contract}',
            '/contratos/{contract}/liquidar',
            '/liquidaciones/{liquidation}',
            '/liquidaciones/{liquidation}/excel',
            '/empresas',
            '/trabajadores',
            '/parametricas/{resource}',
            '/settings/profile',
            '/settings/password',
        ] as $path) {
            self::assertArrayHasKey($path, $spec['paths'], "Falta documentar el path {$path}.");
        }

        self::assertArrayHasKey('WebSession', $spec['components']['securitySchemes']);
        self::assertArrayHasKey('CsrfHeader', $spec['components']['securitySchemes']);
        self::assertSame(['get', 'post'], array_keys($spec['paths']['/login']));
    }

    public function test_la_interfaz_de_swagger_ui_esta_disponible(): void
    {
        $this->get('/documentacion/api')
            ->assertOk()
            ->assertSee('SwaggerUIBundle', false)
            ->assertSee(route('api-docs.spec'), false);
    }

    public function test_los_esquemas_usados_por_los_requestbody_estan_definidos(): void
    {
        $spec = $this->get('/documentacion/api/spec.json')->json();

        foreach (['ContractInput', 'LiquidationInput', 'CompanyInput', 'WorkerInput', 'ContractCard', 'LiquidationDocument', 'LiquidationLine', 'ParametricRow', 'LabeledValue'] as $schema) {
            self::assertArrayHasKey($schema, $spec['components']['schemas'], "Falta el schema {$schema}.");
        }
    }
}
