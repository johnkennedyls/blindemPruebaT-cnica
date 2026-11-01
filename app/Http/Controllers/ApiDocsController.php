<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use OpenApi\Builder;
use OpenApi\Builder\Result;

/**
 * Serves the OpenAPI specification generated from the attributes in
 * app/OpenApi/ and the Swagger UI page that consumes it.
 */
final class ApiDocsController extends Controller
{
    /**
     * Builds the OpenAPI document (v3.1) scanning app/OpenApi/.
     */
    public function build(): Result
    {
        $result = (new Builder)
            ->setVersion('3.1.0')
            ->addSource(base_path('app/OpenApi'))
            ->build();

        abort_unless($result->isValid(), 503, 'La especificación OpenAPI no pudo generarse.');

        return $result;
    }

    /**
     * JSON document consumed by Swagger UI.
     */
    public function spec(): Response
    {
        return response(
            content: $this->build()->toJson(),
            status: 200,
            headers: ['Content-Type' => 'application/json'],
        );
    }

    /**
     * Swagger UI page.
     */
    public function ui(): View
    {
        return view('api-docs.ui');
    }
}
