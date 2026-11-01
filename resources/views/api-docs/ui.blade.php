<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blindem — Documentación de la API (OpenAPI 3.1)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <link rel="icon" type="image/svg+xml" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/favicon-32x32.png">
    <style>
        html {
            box-sizing: border-box;
            overflow-y: scroll;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
        }
        #swagger {
            max-width: 1460px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .docs-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 4px;
        }
        .docs-header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }
        .docs-header a {
            color: #4999d0;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div id="swagger">
        <header class="docs-header">
            <h1>Blindem — Documentación de la API (OpenAPI 3.1)</h1>
            <nav>
                <a href="{{ route('home') }}">← Volver a la aplicación</a>
            </nav>
        </header>
        <div id="swagger-ui"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: "{{ route('api-docs.spec') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                docExpansion: 'list',
                filter: true,
                tryItOutEnabled: true,
            });
        };
    </script>
</body>
</html>
