<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use OpenApi\Builder;
use OpenApi\Builder\Result;

final class ApiDocsController extends Controller
{
    public function build(): Result
    {
        $result = (new Builder)
            ->setVersion('3.1.0')
            ->addSource(base_path('app/OpenApi'))
            ->build();

        abort_unless($result->isValid(), 503, 'La especificación OpenAPI no pudo generarse.');

        return $result;
    }

    public function spec(): Response
    {
        return response(
            content: $this->build()->toJson(),
            status: 200,
            headers: ['Content-Type' => 'application/json'],
        );
    }

    public function ui(): View
    {
        return view('api-docs.ui');
    }
}
