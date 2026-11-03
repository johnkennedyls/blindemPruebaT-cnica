# Documento 4 — Prueba de endpoints, uno por uno

**Proyecto:** Sistema de Liquidación de Prestaciones Sociales e Indemnizaciones
**Entorno de referencia:** Laravel 13 + React (Inertia) + PostgreSQL 18, servidor en `http://localhost:8000`
**Objetivo:** que cualquier persona, sin conocer el código, pueda abrir la aplicación, disparar cada ruta
y comprobar que responde lo que este proyecto promete.

> **Cómo leer este documento.** La sección 2 explica cómo habla esta aplicación (sesión, CSRF, HTML e JSON);
> sin ese contexto, un `403` o un `419` parecen fallos y no lo son. La sección 3 deja un cliente de pruebas
> listo para pegar en PowerShell. La sección 5 es la ficha de cada endpoint, y la 7 es la hoja de firmas
> para el video y la sustentación.

---

## 0. Qué se considera "probar un endpoint"

Una ruta está probada cuando se ven **sus tres caminos**: el caso feliz, el caso de permiso denegado y el
caso de datos inválidos. Esta guía exige los tres,
porque el enunciado del cliente pide un CRUD y una calculadora, y un CRUD sin validación es un formulario
que escribe basura en la base de datos.

Cada ficha indica además **dónde está la prueba automática** que vigila ese mismo comportamiento
(`tests/Feature/...`). Lo que se comprueba a mano y lo que se comprueba en CI son dos caras del mismo
contrato: si alguna se mueve, la otra lo avisa.

No se prueba aquí (fuera de alcance declarado en `docs/01-requerimientos.md`): autenticación por token,
API pública multi-tenant, facturación electrónica, ni el flujo de aprobación de la liquidación.
El auto-registro tampoco: las cuentas se crean con la semilla.

---

## 1. Preparación del entorno

### 1.1 Base de datos y datos de ejemplo

```powershell
cd D:\work\pruebatecnicajohnKennedy\blindem
php artisan migrate:fresh --seed
```

`migrate:fresh` reconstruye las 26 tablas y `--seed` carga los catálogos, los dos usuarios y los 21 contratos
de demostración. La semilla no es decorado: cada contrato está elegido para mostrar una variable distinta
del cálculo (salario por debajo del SMLMV, salario igual al SMLMV, entre 2 y 10 SMLMV, más de 10 SMLMV,
aprendiz SENA, término fijo roto antes del vencimiento, vínculo de dos meses, horas extras, doble vínculo
solapado y trabajador con contratos en varias empresas).

Las cuentas son fijas y solo existen en el entorno local:

| Correo                     | Contraseña | Rol           | Ve                                              |
| -------------------------- | ---------- | ------------- | ----------------------------------------------- |
| `admin@blindem.test`       | `password` | Administrador | Todo: 5 empresas, 16 trabajadores, 21 contratos |
| `colaborador@blindem.test` | `password` | Colaborador   | Solo las tres empresas donde está autorizado    |

Antes de lanzar una sola petición hay que tener abierta la base en **pgAdmin 4** (servidor
`postgresql-x64-18`, rol `blindem`, base `blindem`). Buena parte de las pruebas de esta guía se confirman
mirando la tabla que escribe el endpoint: `contracts`, `liquidations`, `liquidation_items`,
`activity_logs` y el catálogo paramétrico que toque.

Si `php` no se encuentra en la ventana de PowerShell, Herd no está en el `PATH`:

```powershell
$env:PATH = "C:\Users\Usuario\.config\herd\bin\php85;$env:PATH"
```

### 1.2 Servidor

```powershell
php artisan serve          # http://localhost:8000
```

En otra ventana, si se quiere ver los cambios de React sin recompilar:

```powershell
npm run dev                # Vite en el puerto 5173
```

Con `php artisan serve` a secas ya basta para todas las pruebas de este documento, porque
`public/build` está compilado.

### 1.3 Pruebas automáticas

```powershell
php artisan test                                   # 262 pruebas, 1.277 aserciones
php artisan test --filter=LiquidationFlowTest      # la calculadora de punta a punta
php artisan test --filter=DemoSeedLiquidationTest  # liquida los 14 contratos liquidables de la semilla
npm run check; npm run types:check                 # formato y tipos del lado React
```

### 1.4 Documentación viva de la API

`http://localhost:8000/documentacion/api` abre Swagger UI con las rutas de dominio, sus cuerpos de
petición y sus respuestas de error. La especificación no es un archivo que hay que mantener a mano:
`ApiDocsController::build()` recorre las atribuciones OpenAPI de `app/OpenApi/` en cada petición y responde
el JSON, así que la documentación se desactualiza solo si el código se desactualiza antes.

```powershell
curl.exe -s http://localhost:8000/documentacion/api/spec.json > spec.json
php artisan test --filter=ApiDocsTest      # verifica que todos los módulos estén en el spec
```

Swagger sirve para **leer** el contrato y para probar las rutas de lectura. Para las de escritura, el
navegador y PowerShell dan menos sorpresas, porque la sesión y el token CSRF viajan solos.

---

## 2. Cómo habla esta aplicación

### 2.1 No hay tokens: hay sesión de navegador

Todas las rutas de negocio van por el grupo `web`: cookie de sesión `blindem-liquidaciones-session`
(dormida en la tabla `sessions`, porque `.env` fija `SESSION_DRIVER=database`) y cookie `XSRF-TOKEN`
para el token CSRF.
No existe guard de API ni personal access token. Por eso:

- Una petición sin cookie de sesión responde **302 a `/login`** (rutas `auth`) o **200 con la pantalla de login**
  si la ruta es de invitado.
- Una petición con sesión pero sin rol suficiente responde **403**.
- Una petición de escritura sin token CSRF responde **419** (Página expirada). Es el error más frecuente
  de quien prueba con `curl` y olvida la cabecera `X-XSRF-TOKEN`.

### 2.2 El mismo endpoint responde HTML o JSON según el encabezado `Accept`

Los formularios del lado React envían `X-Inertia`, y el servidor contesta la página otra vez (código **303**
tras un guardado correcto, **422** con la lista de errores si la validación falla). Con
`Accept: application/json` en cambio saca los errores de validación como JSON con esta forma:

```json
{
    "message": "El campo código es obligatorio.",
    "errors": { "code": ["El campo código es obligatorio."] }
}
```

`message` repite el primer mensaje de la lista; cuando fallan dos o más campos el texto se amplía con la
cola `(and 1 more error)`. `errors` siempre trae la lista completa, campo por campo, y es la parte que hay
que mirar en las fichas.

Ese es el modo que usan las fichas de este documento: un `422` con el campo Exacto en `errors` es más fácil
de verificar que leer el formulario recargado. El guardado feliz sigue devolviendo **302** con el
`Location` del redirect, y eso también es un resultado válido.

Excepción: `POST /contratos/{contract}/liquidar/vista-previa` siempre devuelve JSON, porque la pantalla la
llama con `fetch` para pintar el desglose antes de guardar.

### 2.3 Tabla de códigos que se esperan en esta aplicación

| Código | Cuándo aparece en Blindem                                                                                                                                                |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `200`  | Lecturas (listados, fichas, el Excel cuando se pide sin download, Swagger)                                                                                               |
| `302`  | Escritura aceptada: el controlador responde con `to_route(...)`                                                                                                          |
| `303`  | Escritura aceptada vista desde Inertia (el navegador la convierte en un GET a la ruta destino)                                                                           |
| `403`  | Rol que no alcanza. Hay dos sabores: el middleware `admin` (empresas, trabajadores, paramétricas) y la política de negocio (un contrato o una liquidación de un tercero) |
| `404`  | El recurso no existe (`No query results for model [App\Models\Contract] 99`), o el slug paramétrico no está en el registro (`ParametricRegistry::resolve`)               |
| `419`  | Falta el token CSRF, o se tomó de una sesión distinta                                                                                                                    |
| `422`  | Validación: formato de fecha, NIT sin dígitos, código repetido, fecha de retiro anterior al ingreso                                                                      |

---

## 3. Cliente de pruebas en PowerShell

Windows ya trae `curl.exe` en `System32`. En PowerShell 5.1 el comando `curl` está reservado como alias de
`Invoke-WebRequest`, así que en todo este documento se escribe `curl.exe`. Lo que sigue es un bloque de
cuatro funciones; se pega una vez en la ventana y se reutiliza.

```powershell
$base = 'http://localhost:8000'

function Get-BlinToken([string]$jar) {
    ([uri]::UnescapeDataString(((Get-Content $jar) | Where-Object { $_ -match 'XSRF-TOKEN' } | ForEach-Object { ($_ -split "`t")[6] })))
}

function New-BlinSession([string]$email, [string]$password, [string]$jar) {
    Remove-Item $jar -ErrorAction SilentlyContinue
    curl.exe -s -c $jar -o NUL "$base/login"
    $code = curl.exe -s -b $jar -c $jar -o NUL -w '%{http_code}' -X POST "$base/login" `
        -H "X-XSRF-TOKEN: $(Get-BlinToken $jar)" `
        --data-urlencode "email=$email" --data-urlencode "password=$password"
    "$email -> login $code (esperado 302)"
}

function Send-Blin([string]$jar, [string]$method, [string]$path, [string]$json) {
    $argv = @('-s', '-b', $jar, '-c', $jar, '-o', 'body.tmp', '-w', '%{http_code}',
              '-X', $method, "$base$path",
              '-H', "X-XSRF-TOKEN: $(Get-BlinToken $jar)",
              '-H', 'Accept: application/json')
    if ($json) {
        $json | Out-File -Encoding ascii payload.tmp
        $argv += @('-H', 'Content-Type: application/json', '--data-binary', '@payload.tmp')
    }
    $code = (& curl.exe @argv)
    $body = Get-Content body.tmp -Raw -ErrorAction SilentlyContinue
    if ($body.Length -gt 300) { $body = $body.Substring(0, 300) + '...' }
    "$method $path -> $code $body"
}
```

Uso:

```powershell
New-BlinSession 'admin@blindem.test' 'password' 'adm.txt'
Send-Blin 'adm.txt' 'GET'  '/documentacion/api/spec.json' $null
Send-Blin 'adm.txt' 'POST' '/parametricas/cargos' '{"code":"PRB-01","name":"Cargo de prueba"}'
```

Cuatro detalles que ahorran media hora de desconcierto:

1. **El token se vuelve a leer antes de cada escritura.** Al iniciar sesión Laravel regenera el token de la
   sesión; si se guarda el primer token en una variable y se reutiliza, todo responde `419`.
2. **`-b` y `-c` van juntos.** `-b` manda las cookies guardadas y `-c` actualiza el archivo; sin `-c` la
   sesión se queda corta a mitad del recorrido.
3. **El cuerpo JSON se escribe a un archivo.** PowerShell 5.1 desarma las comillas dobles cuando se las pasa
   a un `.exe`, y `curl.exe` termina mandando `{code:PRB-01}`: el servidor lee un cuerpo vacío y contesta
   `422` con «el campo código es obligatorio», que parece un fallo de la aplicación y es un fallo del cliente.
   `Out-File -Encoding ascii` evita además el BOM de UTF-8, que tampoco deja parsear el JSON.
4. **El jar es la identidad.** `adm.txt` es administrador y `col.txt` es colaborador. Probar los permisos
   consiste en mandar la misma ruta con los dos archivos.

Para ver el HTML de una pantalla (y no el resumen de 300 caracteres) se quita el `-o body.tmp`:

```powershell
curl.exe -s -b adm.txt "$base/liquidaciones?status=terminado" | Select-String -Pattern 'CTR-0000'
```

---

## 4. Mapa de rutas

### 4.1 Cómo se vuelve a generar el mapa

El mapa no se copia de este documento: se lee del código en cada momento.

```powershell
php artisan route:list | Select-String -Pattern 'contratos|liquidaciones|empresas|trabajadores|parametricas|dashboard|documentacion'
```

La salida completa trae 78 líneas, pero 45 pertenecen al kit de autenticación que trae Laravel
(login, registro, recuperación de contraseña, verificación de correo, dos factores, passkeys y ajustes).
Las **33 rutas de dominio** son las que se recorren en las fichas, y están agrupadas abajo por módulo.
Las dos rutas de documentación (`/documentacion/api` y `/documentacion/api/spec.json`) son las únicas
de dominio que se responden **sin sesión**; las otras 31 redirigen a `/login`.

### 4.2 Tabla de rutas de dominio

La columna «Quién pasa» es el control que decide el permiso: `admin` es el middleware de rol,
`política` es la `ContractPolicy` o la `LiquidationPolicy`, y `todos` es cualquier usuario autenticado
y con el correo verificado.

| Método   | URI                                            | Nombre                 | Mód.        | Quién pasa     |
| -------- | ---------------------------------------------- | ---------------------- | ----------- | -------------- |
| `GET`    | `/dashboard`                                   | `dashboard`            | Panel       | todos          |
| `GET`    | `/documentacion/api`                           | `api-docs.ui`          | Doc. API    | público        |
| `GET`    | `/documentacion/api/spec.json`                 | `api-docs.spec`        | Doc. API    | público        |
| `GET`    | `/empresas`                                    | `companies.index`      | Empresas    | todos          |
| `GET`    | `/empresas/crear`                              | `companies.create`     | Empresas    | `admin`        |
| `POST`   | `/empresas`                                    | `companies.store`      | Empresas    | `admin`        |
| `GET`    | `/empresas/{company}/editar`                   | `companies.edit`       | Empresas    | `admin`        |
| `PUT`    | `/empresas/{company}`                          | `companies.update`     | Empresas    | `admin`        |
| `DELETE` | `/empresas/{company}`                          | `companies.destroy`    | Empresas    | `admin`        |
| `GET`    | `/trabajadores`                                | `workers.index`        | Trabajad.   | todos          |
| `GET`    | `/trabajadores/crear`                          | `workers.create`       | Trabajad.   | `admin`        |
| `POST`   | `/trabajadores`                                | `workers.store`        | Trabajad.   | `admin`        |
| `GET`    | `/trabajadores/{worker}/editar`                | `workers.edit`         | Trabajad.   | `admin`        |
| `PUT`    | `/trabajadores/{worker}`                       | `workers.update`       | Trabajad.   | `admin`        |
| `DELETE` | `/trabajadores/{worker}`                       | `workers.destroy`      | Trabajad.   | `admin`        |
| `GET`    | `/contratos`                                   | `contracts.index`      | Contratos   | todos (filtro) |
| `GET`    | `/contratos/crear`                             | `contracts.create`     | Contratos   | todos          |
| `POST`   | `/contratos`                                   | `contracts.store`      | Contratos   | todos (filtro) |
| `GET`    | `/contratos/{contract}/editar`                 | `contracts.edit`       | Contratos   | política       |
| `PUT`    | `/contratos/{contract}`                        | `contracts.update`     | Contratos   | política       |
| `DELETE` | `/contratos/{contract}`                        | `contracts.destroy`    | Contratos   | política       |
| `GET`    | `/contratos/{contract}/liquidar`               | `liquidations.create`  | Liquidación | política       |
| `POST`   | `/contratos/{contract}/liquidar/vista-previa`  | `liquidations.preview` | Liquidación | política       |
| `POST`   | `/contratos/{contract}/liquidar`               | `liquidations.store`   | Liquidación | política       |
| `GET`    | `/liquidaciones`                               | `liquidations.index`   | Liquidación | todos (filtro) |
| `GET`    | `/liquidaciones/{liquidation}`                 | `liquidations.show`    | Liquidación | política       |
| `GET`    | `/liquidaciones/{liquidation}/excel`           | `liquidations.excel`   | Liquidación | política       |
| `GET`    | `/parametricas/{resource}`                     | `parametrics.index`    | Paramétr.   | `admin`        |
| `GET`    | `/parametricas/{resource}/crear`               | `parametrics.create`   | Paramétr.   | `admin`        |
| `POST`   | `/parametricas/{resource}`                     | `parametrics.store`    | Paramétr.   | `admin`        |
| `GET`    | `/parametricas/{resource}/{parametric}/editar` | `parametrics.edit`     | Paramétr.   | `admin`        |
| `PUT`    | `/parametricas/{resource}/{parametric}`        | `parametrics.update`   | Paramétr.   | `admin`        |
| `DELETE` | `/parametricas/{resource}/{parametric}`        | `parametrics.destroy`  | Paramétr.   | `admin`        |

La columna «todos (filtro)» significa que el listado está filtrado por datos, no por rol: el colaborador ve la pantalla con 200, pero solo los
contratos y las liquidaciones a los que su política le llega. Es la diferencia entre «no puede abrir»
(`403`) y «abre y ve menos» (`200` con el conjunto recortado), y conviene tenerla clara antes de leer
las fichas.

Las seis rutas paramétricas no se repiten cinco veces en el archivo: son seis rutas con `{resource}`
resuelto contra el registro de `app/Http/Parametrics/ParametricRegistry.php`. Los cinco slugs válidos son
`tipos-de-documento`, `cargos`, `tipos-de-contrato`, `motivos-de-retiro` y `salarios-minimos`. Un slug que
no esté en el registro responde `404` desde el controlador, y un `{parametric}` no numérico responde `404`
antes de tocar la base porque la ruta lo declara con `whereNumber`.

### 4.3 Los ids numéricos cambian: se busca por código

Ninguna ficha de este documento fija un id de contrato. Cada vez que se vuelve a sembrar, la tabla
`contracts` arranca en otro número (en la base de desarrollo de esta semana el `CTR-000001` cayó en el id
15 y antes había estado en el 1), así que los ids se consultan, no se recuerdan:

```powershell
function Get-BlinId([string]$class, [string]$column, [string]$value) {
    $php = "echo $class" + "::where('$column', '$value')->value('id');"
    (php artisan tinker --execute $php)
}

$id = Get-BlinId 'App\Models\Contract' 'code' 'CTR-000009'
Send-Blin 'adm.txt' 'GET' "/contratos/$id/liquidar" $null
```

La cadena PHP se arma por partes porque en PowerShell doble comilla `$class::where` se lee como acceso a
miembro estático y no como texto. Con `php artisan test` pasa lo mismo: el nombre del archivo se pasa como
argumento, no se interpola dentro de una cadena.

`php artisan tinker --execute` usa la conexión que ya está configurada en `.env`, así que no hay que
escribir la contraseña de PostgreSQL en la ventana. Para mirar el resultado con detalle se puede usar
pgAdmin, o `Send-Blin` con `?q=` en la lista, que acepta código, nombre del afiliado y número de documento.

La otra forma de conseguir el id, sin salir del navegador, es mirar la barra de direcciones: el botón
«Editar» de cualquier fila de `/contratos` lleva el id en la URL.

---

## 5. Fichas, endpoint por endpoint

Cada ficha tiene las mismas cinco líneas. «Feliz» es lo que debe pasar con datos correctos; «Permiso» es el
mismo pedido hecho con el otro rol; «Validación» manda un cuerpo roto y compara el mensaje literal;
«Prueba automática» dice qué archivo de `tests/` vigilancia ese comportamiento en CI.

### 5.1 Panel y documentación de la API

#### `GET /dashboard`

- **Parámetros:** ninguno. Las tarjetas salen de un conteo sobre `contracts` y `liquidations`.
- **Feliz:** `Send-Blin 'nadie.txt' 'GET' '/dashboard' $null` **sin sesión** responde `302` hacia `/login`;
  con `adm.txt` o `col.txt` responde `200` y en el `data-page` del HTML viajan `summary` (cuatro contadores:
  activos, liquidables, liquidados y liquidaciones) y `pending`, que son hasta cinco contratos en estado
  `terminado` o `en_liquidacion` ordenados por fecha de retiro.
- **Permiso:** no hay regla de rol. El colaborador ve sus propios contadores, porque el conteo pasa por la
  misma scope `visibleTo` que el listado de contratos.
- **Validación:** no aplica, no recibe entrada.
- **Prueba automática:** `tests/Feature/DashboardTest`, seis métodos, incluido
  `test_el_panel_limite_la_lista_de_pendientes_a_cinco_contratos`.

#### `GET /documentacion/api` y `GET /documentacion/api/spec.json`

- **Parámetros:** ninguno. La primera devuelve Swagger UI (HTML); la segunda, el documento OpenAPI 3.1.
- **Feliz:** ambas responden `200` **sin cookie de sesión**, a propósito: son la hoja de lectura del
  proyecto. `Send-Blin` con un jar vacío funciona igual.
- **Qué se mira en el spec:** que estén los cinco módulos de dominio y los cuerpos de petición.

    ```powershell
    curl.exe -s "$base/documentacion/api/spec.json" | Select-String -SimpleMatch '/liquidaciones'
    ```

- **Permiso:** no hay. El spec se construye del código, no de los datos, así que no filtra nada.
- **Validación:** no aplica.
- **Prueba automática:** `tests/Feature/ApiDocsTest`, que recorre el spec y reclama que cada controlador de
  dominio esté descrito.

### 5.2 Empresas

Escritura exclusiva del administrador; lectura abierta a los dos perfiles.

#### `GET /empresas`

- **Parámetros:** `q` (nombre comercial, razón social o NIT, con `ILIKE`), `page`. Doce tarjetas por
  página, ordenadas por razón social, cada una con cuántos contratos tiene.
- **Feliz:** `Send-Blin 'adm.txt' 'GET' '/empresas?q=Andina' $null` → `200`. Con `col.txt` también `200`.
- **Permiso:** los dos roles entran. Lo que cambia es lo que se puede hacer después: el botón «Nueva
  empresa» y el formulario de edición solo aparecen para el administrador.
- **Validación:** un `page` que no existe devuelve la paginadora vacía, no un error; es el comportamiento
  estándar de Laravel y no hay nada que firmar aquí.
- **Prueba automática:** `tests/Feature/CompanyRegistryTest`.

#### `GET /empresas/crear`

- **Feliz:** con `adm.txt` → `200` con el formulario vacío.
- **Permiso:** con `col.txt` → `403`. Lo frena el middleware `admin`, antes de tocar la vista.
- **Prueba automática:** `CompanyRegistryTest::test_el_colaborador_no_puede_alterar_el_registro_de_empresas`.

#### `POST /empresas`

- **Cuerpo:** `nit` (obligatorio, 8 a 15 dígitos con un guion opcional), `business_name` (obligatorio,
  hasta 200), y el resto opcional: `trade_name`, `activity_code`, `address`, `city`, `phone`, `email`,
  `legal_rep_name`, `legal_rep_document`, `is_active`.
- **Feliz:**

    ```powershell
    Send-Blin 'adm.txt' 'POST' '/empresas' '{"nit":"900111222","business_name":"Comercializadora La Colina S.A.S.","trade_name":"La Colina","city":"Bogotá","email":"nomina@lacolina.test"}'
    ```

    `302` hacia `/empresas/{id}/editar` (el guardado lleva a la ficha, no al listado), con el aviso
    «La empresa Comercializadora La Colina S.A.S. quedó registrada.». En pgAdmin aparece la fila en
    `companies`.

- **Permiso:** con `col.txt` → `403` (middleware).
- **Validación:** tres casos que conviene ver en pantalla:

    | Pedido                       | Respuesta                                                 |
    | ---------------------------- | --------------------------------------------------------- |
    | `"nit":"900111222"` repetido | `422` · `Ya existe una empresa registrada con ese NIT.`   |
    | `"nit":"ABC-1122"`           | `422` · `El NIT solo acepta dígitos y un guion opcional.` |
    | `"nit":"123"`                | `422` · el mensaje de `min:8` sobre el campo NIT          |

- **Prueba automática:** `CompanyRegistryTest`.

#### `GET /empresas/{company}/editar` y `PUT /empresas/{company}`

- **Feliz:** `PUT` → `302` de vuelta a la misma ficha de edición, con el aviso
  «Los datos de la empresa fueron actualizados.». La tabla `companies` muestra el cambio y `activity_logs`
  registra la edición.
- **Permiso:** `403` con `col.txt` en las dos rutas. Verificado: `GET /empresas/6/editar` con el jar del
  colaborador responde `403` aunque la empresa exista.
- **Validación:** el `nit` repetido de **otra** empresa da `422`; el propio no, porque la regla `unique`
  se declara con `ignore($this->company()?->id)`. Esa es la prueba de que editar no se pelea consigo mismo.
- **Prueba automática:** `CompanyRegistryTest`.

#### `DELETE /empresas/{company}`

- **Feliz (borrado):** empresa sin contratos → `302` al listado y la fila desaparece de `companies`. Es
  borrado físico: las empresas no tienen `deleted_at`.
- **Casos protegidos:** empresa con contratos → `302` de vuelta **con el error**
  `No se puede eliminar una empresa que ya tiene contratos registrados. Desactívela en su lugar.` El código
  devuelve el mismo `302`; lo que cambia es el mensaje que se lee en la pantalla o en el `data-page`.
- **Permiso:** `403` con `col.txt`.
- **Cómo se comprueba en pgAdmin:** `select id, business_name from companies;` antes y después.
- **Prueba automática:** `CompanyRegistryTest`.

### 5.3 Trabajadores

Todo el módulo es de administrador. El colaborador no registra afiliados: los recibe ya registrados, y por
eso su pantalla de liquidación busca por tipo y número de documento.

#### `GET /trabajadores`

- **Parámetros:** `q` (nombres, apellidos o documento), `page`. Doce por página, ordenados por apellido.
- **Feliz:** `Send-Blin 'col.txt' 'GET' '/trabajadores?q=79456123' $null` → `200`. El documento `79456123`
  es la Cédula de Carlos Andrés Pérez Rojas en la semilla.
- **Permiso:** la ruta es de lectura abierta, pero el **contenido sí se recorta**: quien no es administrador
  solo ve los afiliados que tienen algún contrato en una de sus empresas. Con `adm.txt` salen los 16 de la
  semilla; con `col.txt` salen los que trabajan en Andina, Neumática o Meridian. Es la diferencia entre
  `403` (no entra) y `200` con menos filas (entra y ve su recorte).
- **Prueba automática:** `WorkerRegistryTest::test_el_colaborador_ve_solo_los_trabajadores_de_sus_empresas`.

#### `GET /trabajadores/crear`, `POST /trabajadores`, `GET|PUT /trabajadores/{worker}`, `DELETE /trabajadores/{worker}`

- **Permiso:** las cinco rutas responden `403` con `col.txt`. Verificado en vivo con
  `GET /trabajadores/crear`.
- **Cuerpo:** `document_type_id` y `document_number` obligatorios, `first_names` y `last_names`
  obligatorios, `birth_date` (opcional pero anterior a hoy), `gender` (`m`, `f` o `otro`), `email`, `phone`,
  `address`, `city`, `department`, `is_active`.
- **Feliz:**

    ```powershell
    Send-Blin 'adm.txt' 'POST' '/trabajadores' '{"document_type_id":1,"document_number":"1019887766","first_names":"Prueba de","last_names":"Afiliado","birth_date":"1990-03-10","gender":"m","city":"Bogotá"}'
    ```

    `302` al listado. El tipo `1` es `CC` en la semilla de catálogos.

- **Validación:** los tres mensajes que salen del `WorkerRequest` son literales y se pueden copiar al pie:

    | Pedido                              | Respuesta                                                                        |
    | ----------------------------------- | -------------------------------------------------------------------------------- |
    | `"document_number":"CC-1019887766"` | `422` · `El número de documento solo acepta dígitos y separadores.`              |
    | repetir un documento ya registrado  | `422` · `Ya existe un trabajador registrado con ese tipo y número de documento.` |
    | `"birth_date":"2030-01-01"`         | `422` · `La fecha de nacimiento no puede ser futura.`                            |

    La unicidad del documento es compuesta: `CC 1019887766` y `CE 1019887766` pueden convivir, porque la regla
    filtra por `document_type_id`. Vale la pena probarlo: es de las validaciones que se rompen cuando alguien
    simplifica el `unique`.

- **Borrado protegido:** un trabajador con contratos no se elimina; responde `302` con el error
  `No se puede eliminar un trabajador con contratos registrados. Desactívelo en su lugar.`
- **Prueba automática:** `WorkerRegistryTest`.

### 5.4 Contratos

Aquí no manda el rol sino la política: el colaborador puede crear y editar contratos, pero solo los de las
empresas donde consta como colaborador, y el administrador puede con todos.

#### `GET /contratos`

- **Parámetros:** `q` (código, nombre del afiliado o número de documento), `status` (`activo`,
  `terminado`, `en_liquidacion`), `company_id`, `page`. Diez por página, ordenados por fecha de ingreso
  descendente.
- **Feliz:**

    ```powershell
    Send-Blin 'col.txt' 'GET' '/contratos?status=terminado&company_id=6' $null
    ```

    `200`. Para confirmar el filtro no hay que leer el HTML: el `company_id` correcto se ve en pgAdmin con
    `select code, company_id, status from contracts order by code;`.

- **Permiso:** con `col.txt` solo salen los contratos de Andina, Neumática y Meridian (las tres empresas
  donde la semilla autoriza al colaborador); con `adm.txt` salen los 21.
- **Validación:** un `status` inventado (`?status=tramposo`) se **ignora** en silencio y la lista sale con
  todos los estados. Es decisión de diseño: `ContractStatus::tryFrom()` devuelve `null` y el scope `byStatus`
  trata `null` como «sin filtro». Vale la pena mencionarlo en la sustentación, porque un revisor puede
  esperar un `422`.
- **Prueba automática:** `tests/Feature/ContractRegistryTest`.

#### `GET /contratos/crear`

- **Feliz:** `200` con el formulario y el código sugerido en el campo correspondiente. El sugerido es el
  siguiente número libre con la máscara `CTR-%06d`.
- **Qué más se mira:** los desplegables de empresa vienen ya recortados para el colaborador; el
  administrador ve las cinco.
- **Prueba automática:** `ContractRegistryTest`.

#### `POST /contratos`

- **Cuerpo obligatorio:** `code`, `worker_id`, `company_id`, `contract_type_id`, `position_id`,
  `start_date`, `base_salary_monthly`, `transport_auxiliary_mode`, `status`. Son opcionales
  `scheduled_end_date`, `termination_reason_id`, `end_date`, `salary_effective_from`,
  `transport_auxiliary_monthly`, `overtime_average_monthly`, `pending_salary_days`,
  `pending_vacation_days`, `notes`. Dos reglas dependen del estado: si el contrato va cerrado
  (`terminado`), `end_date` y `termination_reason_id` pasan a ser obligatorios; si el tipo de contrato exige
  fecha de terminación prevista (`FIJO`), `scheduled_end_date` también.
- **Feliz:**

    ```powershell
    Send-Blin 'col.txt' 'POST' '/contratos' '{"code":"ctr-prb-999","worker_id":6,"company_id":6,"contract_type_id":1,"position_id":1,"start_date":"2026-01-15","base_salary_monthly":"2100000","transport_auxiliary_mode":"auto","status":"activo"}'
    ```

    `302` hacia `/contratos/{id}/editar` con el aviso «El contrato CTR-PRB-999 quedó registrado.». Obsérvese
    que el código se mandó en minúsculas: `prepareForValidation()` lo sube a mayúsculas, así que en la base
    queda `CTR-PRB-999`. Esa normalización es parte del contrato de la ruta, no un adorno.

- **Permiso:** el colaborador **sí** puede crear contratos; lo que no puede es crearlos en una empresa ajena
  (ver validación). El administrador puede en cualquiera.
- **Validación:** cuatro casos probados en vivo, todos con `422` y el mensaje literal:

    | Pedido                                           | Respuesta                                                                                |
    | ------------------------------------------------ | ---------------------------------------------------------------------------------------- |
    | repetir un `code` existente                      | `Ya existe un contrato registrado con ese código.`                                       |
    | `"company_id":10` siendo colaborador de 6, 7 y 9 | `Solo puede registrar contratos para las empresas en las que colabora.`                  |
    | `"status":"liquidado"`                           | `El estado Liquidado lo fija el sistema al guardar la liquidación, no se asigna a mano.` |
    | `"end_date"` anterior a `"start_date"`           | `La fecha de retiro no puede ser anterior a la fecha de ingreso.`                        |

    El mismo cuerpo con `company_id:10` y el jar del administrador responde `302`: la regla solo castiga al
    que no es administrador. Es la prueba limpia de que el control está en el rol y no en el dato.

- **Límite legal del plazo fijo:** un `FIJO` (`contract_type_id:2`) con `scheduled_end_date` más allá del
  tope estatutario del tipo responde `422` con
  `El tipo … admite un plazo máximo de N meses. La fecha de terminación prevista pasa de ese límite.` El tope
  viene del catálogo, así que se puede editar desde `/parametricas/tipos-de-contrato` y volver a probar.
- **Prueba automática:** `ContractRegistryTest`, incluidos
  `test_el_colaborador_no_puede_registrar_contratos_en_empresas_ajenas` y
  `test_un_trabajador_puede_tener_dos_contratos_vigentes_en_el_mismo_rango`.

#### `GET /contratos/{contract}/editar`

- **Feliz:** `200` con el formulario precargado y las fechas del contrato.
- **Permiso:** `403` si el contrato es de una empresa donde el usuario no colabora y no lo creó él
  (verificado con un contrato de El Roble: `403` con `col.txt`, `200` con `adm.txt`). También `403` si el
  contrato ya está `liquidado`: la edición se cierra sola al guardarse la liquidación.
- **Prueba automática:** `ContractRegistryTest`.

#### `PUT /contratos/{contract}`

- **Nota de forma:** la ruta acepta `PUT` y `DELETE`. Un `PATCH` responde `405` con
  `The PATCH method is not supported for route contratos/{id}. Supported methods: PUT, DELETE.`, que es el
  error típico de quien llega con la costumbre de otros frameworks.
- **Feliz:** `302` a la misma ficha con el aviso «El contrato … fue actualizado.».
- **Validación de estado:** poner `activo` a un contrato que ya tiene liquidaciones responde `422` con
  `Un contrato con liquidaciones registradas no puede volver a estar activo.`
- **Prueba automática:** `ContractRegistryTest`.

#### `DELETE /contratos/{contract}`

- **Feliz:** `302` al listado con el aviso «El contrato … fue eliminado.». Es **borrado lógico**: la fila
  sigue en `contracts` con `deleted_at` lleno, y por eso las rutas de lectura responden `404` después
  (`No query results for model [App\Models\Contract] {id}`) pero la historia contable no se pierde.
- **Casos protegidos:** `403` si el contrato está `liquidado` (la política lo niega antes de mirar nada
  más), y `302` con el error «El contrato … ya tiene liquidaciones y no se puede eliminar.» si tiene
  liquidaciones pero sigue abierto.
- **Prueba automática:** `ContractRegistryTest::test_no_se_puede_eliminar_un_contrato_con_liquidaciones`.

### 5.5 Liquidación

El corazón del proyecto. Cuatro rutas viven debajo de `/contratos/{contract}` y tres debajo de
`/liquidaciones`.

#### `GET /contratos/{contract}/liquidar` — formulario

- **Qué trae la pantalla:** los datos del contrato, las dos fechas editables (ingreso y liquidación), el
  desglose en vivo, los días de vacaciones y salario pendiente, y los conceptos manuales de préstamo y
  otros descuentos.
- **Feliz:** `200` sobre un contrato `terminado` o `en_liquidacion`. Con la semilla sirven, por ejemplo,
  `CTR-000009` (Óscar Iván Bernal, 4.800.000 con horas extras) y `CTR-000019` (Luz Adriana Cardona, que la
  semilla deja expresamente en `en_liquidacion`).
- **Permiso:** `403` sobre un contrato `activo` y `403` sobre uno `liquidado`. Verificado en vivo: la razón
  es `ContractStatus::canBeLiquidated()`, que solo admite los dos estados anteriores. Este es el detalle que
  deja fuera de la calculadora al `CTR-000004` de Jorge Enrique Roa: no es un fallo, es que está vigente.
- **Prueba automática:** `LiquidationFlowTest::test_un_contrato_activo_no_se_liquida`.

#### `POST /contratos/{contract}/liquidar/vista-previa` — calcular sin guardar

- **Parámetros:** los mismos del guardado, y siempre responde JSON aunque se le mande `Accept: text/html`.
- **Feliz:**

    ```powershell
    Send-Blin 'adm.txt' 'POST' "/contratos/$id/liquidar/vista-previa" '{"settlement_date":"2025-10-31","variant":"con_indemnizacion","vacation_days":18,"pending_salary_days":15,"manual_values":{"DESC_PRESTAMOS":"120000","DESC_OTRAS":"0"}}'
    ```

    `200` con `lines`, `totals`, `notices` y `indemnization_rule`. Cada línea trae `code`, `name`, `side`,
    `base`, `quantity`, `divisor`, `formula` y `amount`. La primera del ejemplo, para `CTR-000009`:

    ```json
    {
        "code": "CESANTIAS",
        "name": "Cesantías",
        "side": "earning",
        "base": "5250000",
        "quantity": "301",
        "divisor": 360,
        "formula": "(Base Prestacional × Días) / 360",
        "amount": "4389583"
    }
    ```

    La base prestacional `5.250.000` es el salario `4.800.000` más las horas extras `450.000`, y `5.250.000 × 301 / 360`
    da `4.389.583` con redondeo a pesos. Se puede comprobar con calculadora en la sustentación: es la
    verificación más barata que existe de que la ruta no está restando ni sumando de más.

- **Qué NO hace:** no escribe una sola fila. Después de la vista previa, `select count(*) from liquidations;`
  sigue con el mismo número. Se puede repetir diez veces con el mismo cuerpo y el resultado es idéntico.
- **Permiso:** el mismo contrato de un tercero responde `403` en esta ruta, igual que en el formulario.
- **Validación:** `422` con los mensajes de `StoreLiquidationRequest`, por ejemplo `"vacation_days":400` →
  `El campo los días de vacaciones no puede ser mayor que 360.`
- **Prueba automática:** `LiquidationFlowTest::test_la_vista_previa_devuelve_el_desglose_sin_guardar_nada`.

#### `POST /contratos/{contract}/liquidar` — guardar

- **Cuerpo de referencia** (es el que usa el formulario al pulsar «Guardar liquidación»):

    ```json
    {
        "settlement_date": "2026-06-30",
        "variant": "prestacional",
        "vacation_days": 1,
        "pending_salary_days": 20,
        "manual_values": { "DESC_PRESTAMOS": "0", "DESC_OTRAS": "0" },
        "observations": "guardado de prueba para la guia de endpoints"
    }
    ```

- **Feliz:** `302` hacia `/liquidaciones/{id}` con el aviso «La liquidación LIQ-2026-00002 quedó guardada.».
  Este guardado se ejecutó en vivo sobre `CTR-000020` y dejó en la base:
    - **once** líneas en `liquidation_items`;
    - `total_earned` 3.139.521, `total_deductions` 85.512, `net_total` 3.054.009, y se cumple
      `neto = ganado − deducido` al peso;
    - `period_from` 2026-05-01 y `period_to` 2026-06-30, tomados de las dos fechas del formulario;
    - el contrato pasó a `liquidado`, y desde ese momento `PUT /contratos/{id}` y un segundo
      `POST /contratos/{id}/liquidar` responden `403`.
- **El folio no es la clave:** el renglón se creó con `id` 6 y folio `LIQ-2026-00002`. El folio se numera
  por año de la fecha de liquidación, con bloqueo de fila para que dos guardados simultáneos no arranquen
  dos `LIQ-2026-00002`.
- **Una línea que desconcierta:** con la variante `prestacional` aparece `INDEMNIZACION` en el desglose con
  `quantity` 0 y la fórmula «No aplica según el motivo de retiro». No es un error: el documento del cliente
  lista los once conceptos siempre, y muestra cero donde no toca. Lo que cambia entre variantes es el monto,
  no la cantidad de renglones.
- **Un dato que hay que saber antes de comparar con el Excel:** los contratos en modo `auto` toman el
  auxilio de transporte del catálogo `salarios-minimos` del año de la fecha de liquidación. La fila de 2026
  lleva `1.750.905 / 249.095`, que son los valores del Excel de referencia y no un decreto del año; el
  origen está escrito en la propia columna `source` de la tabla.
- **Validación:** además del `422` de días, `"settlement_date"` anterior al ingreso responde
  `La fecha de liquidación no puede ser anterior a la fecha de ingreso.`
- **Prueba automática:** `LiquidationFlowTest`, `tests/Feature/Persistence/LiquidationPersistenceTest` (folio
  y snapshot) y `tests/Feature/Calculation/LiquidacionConformeAlExcelTest` (los números del Excel del
  cliente). `DemoSeedLiquidationTest` liquida los catorce contratos liquidables de la semilla de una sola
  pasada, que es la prueba que más casos cubre por peso invertido.

#### `GET /liquidaciones`

- **Parámetros:** `status`, `document_type_id`, `document_number`, `page`. **No hay `q`**: la pantalla pide
  tipo y número de documento, y el número se compara exacto, no con `ILIKE`.
- **Feliz:** `Send-Blin 'col.txt' 'GET' '/liquidaciones' $null` → `200`.
- **Permiso:** el listado sale filtrado por la misma política de datos que los contratos.
- **Prueba automática:** `LiquidationFlowTest`.

#### `GET /liquidaciones/{liquidation}`

- **Feliz:** `200` con la vista imprimible: encabezado, once líneas con su traza de cálculo, totales,
  avisos, antigüedad y el bloque de firmas.
- **Permiso:** `403` para un tercero. El administrador puede ver **todas**, porque `LiquidationPolicy::view`
  empieza con `$user->role->isAdmin()`.
- **Qué verificar en pantalla:** el número de la fórmula de cada renglón coincide con `calc_trace` guardado,
  no se recalcula al vuelo.
- **Prueba automática:** `LiquidationFlowTest` y `LiquidationPersistenceTest`.

#### `GET /liquidaciones/{liquidation}/excel`

- **Feliz:** `200` con `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`,
  cuerpo binario y nombre de archivo `{folio}.xlsx`. Con `curl` hay que dejar el disco escribir el archivo
  en vez de imprimirlo:

    ```powershell
    curl.exe -s -b adm.txt -L -o "$env:USERPROFILE\Downloads\LIQ.xlsx" "$base/liquidaciones/1/excel"
    ```

- **Qué se mira dentro:** la hoja de liquidación con el mismo orden de renglones que la pantalla, más una
  hoja extra «Instrucciones y Normativa» que no aparece en la vista HTML.
- **Permiso:** `403` para un tercero; el administrador puede descargar todas.
- **Prueba automática:** `LiquidationFlowTest` (la descarga) y las pruebas del exportador en
  `tests/Feature/Calculation/`.

### 5.6 Las cinco paramétricas

Las seis rutas de `/parametricas/{resource}` se atienden una sola vez y valen para los cinco catálogos,
porque el controlador no conoce tablas: pregunta al registro y actúa según la definición. Lo que cambia de
un catálogo a otro son los campos del cuerpo, la columna única y la relación que bloquea el borrado.

| `resource`           | Tabla                 | Campos del cuerpo                                                                                  | Obligatorio                                                   | Único  | Bloquea el borrado si…             |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- | ------ | ---------------------------------- |
| `tipos-de-documento` | `document_types`      | `code`, `name`, `abbreviation`, `holder_type` (`natural`, `juridica`, `both`)                      | `code`, `name`, `holder_type`                                 | `code` | hay trabajadores con ese documento |
| `cargos`             | `positions`           | `code`, `name`, `area`, `level`                                                                    | `code`, `name`                                                | `code` | hay contratos con ese cargo        |
| `tipos-de-contrato`  | `contract_types`      | `code`, `name`, `modality`, `statutory_max_months`, `requires_scheduled_end`, `indemnization_rule` | `code`, `name`, `modality`, `indemnization_rule`              | `code` | hay contratos con ese tipo         |
| `motivos-de-retiro`  | `termination_reasons` | `code`, `description`, `classification`, `indemnization_rule`, `applies_sanction_art65`            | `code`, `description`, `classification`, `indemnization_rule` | `code` | hay contratos con ese motivo       |
| `salarios-minimos`   | `minimum_wages`       | `year`, `smlmv`, `transport_auxiliary`, `source`                                                   | `year`, `smlmv`, `transport_auxiliary`                        | `year` | nunca (es histórico)               |

Los campos de tipo casilla (`requires_scheduled_end` y `applies_sanction_art65`) nunca son obligatorios: si
no llegan en el cuerpo, `prepareForValidation()` los entiende como `false`.

#### `GET /parametricas/{resource}`

- **Parámetros:** `q`, `page`. Doce por página. En salarios mínimos se ordena por año; en el resto, por
  código. La búsqueda es `ILIKE` sobre las columnas declaradas en `searchAttributes`.
- **Feliz:** `Send-Blin 'adm.txt' 'GET' '/parametricas/motivos-de-retiro?q=JUSTA' $null` → `200`.
- **Qué se mira en cada fila:** la columna de usos, que llega en el prop `usage_count` y es un `withCount`
  sobre la relación declarada en `usageRelation`. En salarios mínimos la columna no existe
  (`usageRelation: null`), y de ahí que ese catálogo nunca bloquee un borrado. La columna de usos es el dato
  que anticipa el borrado bloqueado: si es mayor que cero, el botón de eliminar va a fallar con el aviso.

#### `GET /parametricas/{resource}/crear`, `GET /parametricas/{resource}/{parametric}/editar`

- **Feliz:** `200` con el formulario construido desde la definición: un campo por `ParametricField`, con su
  `label`, su `hint` y su validación del lado React.
- **Casos de ruta:** un `resource` que no está en el registro responde `404` desde el controlador
  (`/parametricas/inventos` → `404`); un `{parametric}` que no existe responde `404` de binding
  (`No query results for model`); un `{parametric}` con letras ni siquiera llega a la base, porque la ruta
  lo declara `whereNumber`.
- **Permiso:** las dos `403` con `col.txt`.

#### `POST /parametricas/{resource}` y `PUT /parametricas/{resource}/{parametric}`

- **Feliz:** `302` a la ficha de edición del renglón recién escrito. En pgAdmin se ve la fila nueva y, en el
  caso de salarios mínimos, `effective_from` y `effective_to` rellenos a partir de `year`: no son campos del
  formulario, los deriva el controlador.
- **Permiso:** `403` con `col.txt` en las dos rutas, verificado sobre `/parametricas/cargos`.
- **Validación:** el caso que hay que ver con los ojos propios es la unicidad, porque el mensaje depende del
  catálogo:

    ```powershell
    Send-Blin 'adm.txt' 'POST' '/parametricas/cargos' '{"code":"PRB-01","name":"Cargo de prueba"}'
    Send-Blin 'adm.txt' 'POST' '/parametricas/cargos' '{"code":"PRB-01","name":"Otro nombre"}'
    Send-Blin 'adm.txt' 'POST' '/parametricas/salarios-minimos' '{"year":2026,"smlmv":1800000,"transport_auxiliary":250000}'
    ```

    El primer pedido crea (`302`), el segundo responde `422` con `Ya existe un cargo con ese código.`, y el
    tercero `422` por repetir el año. En edición la misma regla no castiga al renglón contra sí mismo, porque
    se declara con `Rule::unique(...)->ignore($this->route('parametric'))`: cambiarle el nombre a `ANL-OP` sin
    cambiarle el código pasa, y crear otro `ANL-OP` no.

- **Los selectores vienen del enum, no de la base:** `modality`, `classification`, `holder_type` e
  `indemnization_rule` se validan con `Rule::in()` sobre los casos del enum. Un valor inventado
  (`"modality":"teletransporte"`) responde `422`, y esa es la razón por la que el motor nunca se encuentra
  con una modalidad que no sepa calcular.
- **Prueba automática:** `tests/Feature/ParametricRegistryTest`, incluidos
  `test_un_recurso_desconocido_responde_404` y `test_no_se_puede_duplicar_el_anio_del_smlmv`.

#### `DELETE /parametricas/{resource}/{parametric}`

- **Feliz:** `302` al listado y la fila fuera de la tabla. Es borrado **físico**: los catálogos no tienen
  `deleted_at`. Verificado con un cargo de prueba sin usos: `DELETE /parametricas/cargos/{id}` → `302`, y el
  `GET /parametricas/cargos/{id}/editar` posterior responde `404`.
- **Caso protegido:** borrar `ANL-OP`, que está en `CTR-000001`, responde `302` de vuelta al listado con el
  error `No se puede eliminar: hay registros que usan este cargo. Desactívelo en su lugar.` Los dos casos dan
  `302`; lo que distingue uno de otro es el aviso y que la fila siga o no en la tabla.
- **Nota honesta sobre el checkbox `applies_sanction_art65`:** se guarda, se lista y aparece en la etiqueta
  de detalle del motivo, pero el motor no calcula la sanción del artículo 65 (moratorios e intereses) porque
  el Excel de referencia no la incluye. La casilla es dato declarativo, no un interruptor de cálculo, y así
  conviene decirlo en la sustentación en vez de insinuar que falta una prueba.
- **Prueba automática:** `ParametricRegistryTest::test_no_se_puede_eliminar_un_cargo_usado_por_contratos`.

### 5.7 Autenticación y ajustes

Las 45 rutas del kit no se fichan una por una: se comprueba el comportamiento que afecta al resto de la
guía.

- **`POST /login`** con credenciales correctas responde `302` a `/dashboard` y deja el jar listo. Con las
  malas, `302` de vuelta a `/login` con `errors.email` en la sesión. Cinco intentos fallidos seguidos
  bloquean por minuto (`throttle:login`), y el síntoma es un `429`: si de pronto nada funciona, es el
  limitador, no la aplicación.
- **Los dos usuarios de la semilla ya tienen el correo verificado.** Un usuario creado por `/register` no, y
  por eso las rutas `verified` lo mandan a `/email/verify`. En el entorno local el correo no sale: se
  imprime con `php artisan tinker` o se lee en `storage/logs/laravel.log` si `MAIL_MAILER=log`.
- **`POST /logout`** responde `302` a `/` y deja el jar inservible: la prueba rápida de que el jar es la
  identidad es volver a mandar el mismo pedido después del logout y ver el `302` a `/login`.
- **`/register`** crea cuentas con rol `collaborator`. Es una decisión de entorno de desarrollo que queda
  declarada en `config/fortify.php`; para un panel cerrado se desactiva con quitar `Features::registration()`.
- **Ajustes** (`/settings/profile`, `/settings/security`, `/settings/password`, `/settings/appearance`) son
  del kit y del usuario propio: no tocan datos de negocio y no requieren firma en la hoja de verificación.
- **Pruebas automáticas:** `tests/Feature/Auth/AuthenticationTest`, `RegistrationTest`,
  `EmailVerificationTest`, `PasswordResetTest`, y `tests/Feature/Settings/ProfileUpdateTest` y
  `SecurityTest`.

---

## 6. Recorridos guiados, uno por perfil

Los dos recorridos están escritos para hacerse seguidos, con el navegador en una ventana y PowerShell en
otra. Sirven para el video: cada paso deja una evidencia visible en pantalla y una fila en la base.

Antes de empezar, `php artisan migrate:fresh --seed`. Con la semilla recién cargada hay cinco empresas,
16 trabajadores, 21 contratos y **14 contratos liquidables**. Cada liquidación que se guarda consume uno: el
contrato pasa a `liquidado` y ya no aparece en el listado de pendientes. Si se repite el recorrido, se
vuelve a sembrar.

### 6.1 Recorrido del colaborador

Se entra con `colaborador@blindem.test` / `password`.

| #   | Paso                                                           | Lo que tiene que salir                                          |
| --- | -------------------------------------------------------------- | --------------------------------------------------------------- |
| 1   | `/dashboard`                                                   | Cuatro tarjetas de resumen y hasta cinco pendientes.            |
| 2   | `/contratos?status=terminado`                                  | Solo contratos cerrados de sus tres empresas.                   |
| 3   | Filtrar por empresa (Neumática)                                | La lista se recorta; cambiar a El Roble da la lista vacía.      |
| 4   | `/liquidaciones` y filtrar por CC 1023456789                   | Búsqueda por documento exacto, sin `q` libre.                   |
| 5   | Abrir `CTR-000009` y pulsar «Liquidar»                         | Formulario con las dos fechas y el desglose en vivo.            |
| 6   | Cambiar la fecha de liquidación y volver a ver                 | El renglón de salario pendiente cambia con la fecha.            |
| 7   | Guardar la liquidación                                         | `302` a la vista imprimible, folio nuevo, contrato `liquidado`. |
| 8   | Botón «Excel»                                                  | Descarga `{folio}.xlsx` con la hoja de normativa.               |
| 9   | Intentar `/parametricas/cargos`                                | `403`. El menú lateral no muestra el enlace.                    |
| 10  | Intentar `/empresas/crear` y `/trabajadores/crear`             | `403` en las dos.                                               |
| 11  | Crear un contrato en empresa ajena (si el desplegable la deja) | `422` con el mensaje de empresas autorizadas.                   |
| 12  | Abrir `CTR-000004` (Jorge Enrique Roa, vigente)                | `403` en «Liquidar»: un contrato activo no se liquida.          |

Los pasos 9 a 12 son la parte que responde al enunciado del cliente sobre permisos. Conviene hacerlos con
la consola de PowerShell abierta mostrando los `403`, porque en el navegador se ven como botones que no
están, y eso es más difícil de explicar en un video.

### 6.2 Recorrido del administrador

Se sale con `POST /logout` y se entra con `admin@blindem.test` / `password`.

| #   | Paso                                                           | Lo que tiene que salir                                     |
| --- | -------------------------------------------------------------- | ---------------------------------------------------------- |
| 1   | `/dashboard`                                                   | Los 21 contratos entran en el conteo.                      |
| 2   | `/trabajadores`                                                | Los 16 afiliados, con su contador de contratos.            |
| 3   | Crear un trabajador con un documento ya usado en CC            | `422` · «Ya existe un trabajador registrado…».             |
| 4   | Crear el mismo número con tipo CE                              | `302` · la unicidad es compuesta, aquí pasa.               |
| 5   | `/empresas` → `/empresas/crear` → guardar                      | `302` a la ficha, con la empresa nueva en el listado.      |
| 6   | Borrar una empresa con contratos                               | `302` + error «…ya tiene contratos registrados…».          |
| 7   | `/parametricas/tipos-de-documento` y los otros cuatro          | Los cinco catálogos navegables, con 12 por página.         |
| 8   | Crear un cargo `PRB-01`, crear `PRB-01` otra vez               | `302` y luego `422` · «Ya existe un cargo con ese código.» |
| 9   | Borrar el cargo `ANL-OP` (usado por `CTR-000001`)              | `302` + error «hay registros que usan este cargo».         |
| 10  | Borrar el cargo `PRB-01` (sin usos)                            | `302` y la fila fuera de la tabla.                         |
| 11  | `/parametricas/salarios-minimos` → duplicar el año 2026        | `422` por unicidad de año.                                 |
| 12  | Abrir `CTR-000010` (Gloria Sáenz, 22.000.000) y liquidar       | Regla de indemnización del Art. 64 lit. b (20 + 15).       |
| 13  | Abrir `CTR-000018`, fijo roto antes del vencimiento            | Indemnización por el plazo restante.                       |
| 14  | Abrir `CTR-000019` (Luz Adriana, `en_liquidacion`)             | Entra a la calculadora igual que un terminado.             |
| 15  | Ver los dos contratos de Natalia (`CTR-000014` y `CTR-000015`) | Los dos `activo` con el rango de fechas solapado.          |

El paso 15 es la demostración visual de que se quitó la restricción de solapamiento: la misma cédula, dos
vínculos vigentes en dos empresas distintas, ambos liquidables por separado.

### 6.3 Qué demuestra cada contrato de la semilla

Los 21 contratos no son relleno. Esta es la lista con el caso de cálculo que cada uno trae, escrita sobre
la semilla recién cargada.

| Contrato   | Afiliado               | Salario    | Caso que pone a prueba                                                                           |
| ---------- | ---------------------- | ---------- | ------------------------------------------------------------------------------------------------ |
| CTR-000001 | Ana Lucía Martínez     | 1.750.905  | El renglón del Excel del cliente, con auxilio manual y horas extras.                             |
| CTR-000002 | Carlos Andrés Pérez    | 2.600.000  | Retiro voluntario: sin indemnización, y sin auxilio de transporte.                               |
| CTR-000003 | Marta Lucía Gómez      | 1.300.000  | Fijo que vence en su fecha: no hay indemnización por vencimiento.                                |
| CTR-000004 | Jorge Enrique Roa      | 3.200.000  | Contrato **activo**: la calculadora lo bloquea con `403`.                                        |
| CTR-000005 | Luisa Fernanda Cardozo | 1.850.000  | Obra o labor con horas extras y mutuo acuerdo, 20 y 20 días.                                     |
| CTR-000006 | Diana Marcela Quintero | 1.150.000  | Salario por debajo del SMLMV del año, despido con justa causa.                                   |
| CTR-000007 | Héctor Julio Álvarez   | 1.423.500  | Salario clavado en el SMLMV de 2025: el auxilio entra entero.                                    |
| CTR-000008 | Sandra Milena Duarte   | 2.000.000  | Fijo con vencimiento ya pasado, liquidado en 2025.                                               |
| CTR-000009 | Óscar Iván Bernal      | 4.800.000  | Entre 2 y 10 SMLMV con horas extras altas, despido sin causa.                                    |
| CTR-000010 | Gloria Esperanza Sáenz | 22.000.000 | Más de 10 SMLMV: Art. 64 literal b (20 + 15 días). Empresa ajena al colaborador.                 |
| CTR-000011 | Yeison Andrés Rincón   | 1.300.000  | Aprendiz SENA: base y auxilio sin derecho.                                                       |
| CTR-000012 | Fabián Andrés Camargo  | 1.600.000  | Primer vínculo (terminado) del trabajador con dos empresas.                                      |
| CTR-000013 | Fabián Andrés Camargo  | 2.150.000  | Su contrato activo en otra empresa: histórico por afiliado.                                      |
| CTR-000014 | Natalia Jiménez        | 2.400.000  | Primera de las dos vigentes **solapadas**.                                                       |
| CTR-000015 | Natalia Jiménez        | 1.900.000  | Segunda vigente del mismo rango, en otra empresa.                                                |
| CTR-000016 | Carlos Andrés Pérez    | 2.900.000  | El que se liquidó en CTR-000002 vuelve a estar activo.                                           |
| CTR-000017 | Marta Lucía Gómez      | 1.500.000  | Contrato recién iniciado en 2026.                                                                |
| CTR-000018 | Julio María Sandoval   | 1.700.000  | Fijo roto **antes** del vencimiento: indemnización por el plazo restante. Empresa de un tercero. |
| CTR-000019 | Luz Adriana Cardona    | 1.850.000  | Estado `en_liquidacion`, para probar que también entra.                                          |
| CTR-000020 | Sebastián Flórez       | 1.600.000  | Vínculo de dos meses: días proporcionales mínimos.                                               |
| CTR-000021 | Luisa Fernanda Cardozo | 2.750.000  | Segunda obra o labor, vigente, con horas extras.                                                 |

---

## 7. Hoja de verificación

Para firmar durante el video o la sustentación. Una línea por grupo de rutas; la columna «pasa cuando» es el
criterio, no una opinión.

| Grupo               | Pasa cuando                                                                                                                                 | ☐   |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| Panel               | Sin sesión devuelve `302` a `/login`, y con sesión las cuatro tarjetas coinciden con pgAdmin.                                               | ☐   |
| Empresas            | El colaborador obtiene `403` en las cinco rutas de escritura; el administrador crea, edita y no puede borrar una con contratos.             | ☐   |
| Trabajadores        | Documento repetido da `422`, el mismo número en otro tipo da `302`, y el listado del colaborador está recortado a sus empresas.             | ☐   |
| Contratos           | Alta `302`, código duplicado `422`, estado `liquidado` a mano `422`, empresa ajena `422` para el colaborador y `302` para el administrador. | ☐   |
| Contratos (cierre)  | Un contrato `liquidado` no se edita (`403`) y borrar uno sin liquidaciones deja la fila con `deleted_at`.                                   | ☐   |
| Calculadora         | La vista previa responde `200` con JSON y **no** crea filas; el mismo cálculo repetido da el mismo monto.                                   | ☐   |
| Guardado            | Un guardado deja once renglones, `neto = ganado − deducido`, folio nuevo y contrato en `liquidado`.                                         | ☐   |
| Permisos de cálculo | Contrato activo `403`, contrato liquidado `403`, liquidación de un tercero `403`.                                                           | ☐   |
| Excel               | Descarga `200` con el tipo MIME de hoja de cálculo, nombre `{folio}.xlsx` y la hoja de normativa.                                           | ☐   |
| Paramétricas        | Los cinco catálogos abren, el duplicado da `422`, el borrado en uso da el aviso y el libre borra.                                           | ☐   |
| Perfiles            | El menú lateral del colaborador no tiene paramétricas, y el del administrador sí tiene las cinco.                                           | ☐   |
| Documentación viva  | `/documentacion/api/spec.json` responde `200` sin sesión y menciona los cinco módulos.                                                      | ☐   |
| Pruebas automáticas | `php artisan test` en verde, `vendor/bin/phpstan` y `npm run check` sin salida.                                                             | ☐   |

Las tres últimas filas son las que sostienen las doce anteriores: si un endpoint cambia y alguien no se dio
cuenta en el navegador, la prueba automática o el spec vivo lo reclaman.
