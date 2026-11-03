# Blindem — Liquidadora de prestaciones sociales

Aplicación web para registrar trabajadores, empresas y contratos, y calcular la liquidación de
prestaciones sociales de un trabajador en Colombia: cesantías, intereses sobre cesantías, prima de
servicios, vacaciones, salario pendiente, auxilio de transporte, horas extras y descuentos de salud y
pensión, con la indemnización cuando el contrato termina sin justa causa.

Cada cálculo sale de un motor independiente que recibe el contrato y los parámetros legales
vigentes; ningún número se escribe a mano en la interfaz. Los montos se manejan con aritmética de
precisión (`bcmath`) y escalas fijas, de forma que el resultado en pantalla coincide con el del
documento exportable, al peso. La indemnización del artículo 64 C.S.T. se resuelve con el patrón
estrategia sobre cinco clases: `IndemnizationStrategyFactory` elige entre `SinIndemnizacion`,
`IndefinidoMenos10Smlmv` (literal a, 30 días el primer año y 20 por cada año adicional),
`IndefinidoMas10Smlmv` (literal b, 20 y 15) y `TerminoFijoPlazoRestante` (los salarios que falten
hasta el vencimiento pactado); las dos de término indefinido heredan de `ProportionalByYears`, la
base abstracta que arma el conteo de días —primer año, años siguientes y fracción proporcional— y
deja el texto de la fórmula. La elección depende de la regla del motivo de retiro, la modalidad del
contrato y el umbral de 10 SMLMV. Los conteos de días no están escritos en el código: se leen de la
tabla `legal_parameters` bajo las claves `indemnization_first_year_days_low`,
`indemnization_per_year_days_low` y sus equivalentes `*_high`.

## Requisitos

| Componente | Versión usada                                                               |
| ---------- | --------------------------------------------------------------------------- |
| PHP        | 8.5 (el `composer.json` pide `^8.3`), con `bcmath` y `pdo_pgsql` activadas  |
| Composer   | 2.x                                                                         |
| Node       | 24 (con npm)                                                                |
| PostgreSQL | 18.6                                                                        |
| Laravel    | 13.39 (el `composer.json` pide `^13.17`)                                    |
| Frontend   | Inertia 3.6, React 19 con TypeScript, Tailwind CSS 4 y Vite (vite-plus 1.4) |

`bcmath` no es opcional: el motor de dinero llama funciones `bc*` para sumar y dividir montos sin
enterarse de los errores del punto flotante.

## Instalación

### 1. Código y dependencias

```bash
git clone <url-del-repositorio> blindem
cd blindem
composer install
npm install
```

### 2. Configuración

```bash
cp .env.example .env
php artisan key:generate
```

En `.env` hay que dejar la sección de base de datos así, poniendo la contraseña del rol que se crea
en el paso 3:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blindem
DB_USERNAME=blindem
DB_PASSWORD=blindem
```

`SESSION_DRIVER=database` y `QUEUE_CONNECTION=database` usan las tablas que crean las migraciones,
así que no hace falta configurar nada más.

### 3. Rol y bases en PostgreSQL

La aplicación no crea roles ni bases: eso se hace una vez como superusuario. Desde `psql` o desde el
Query Tool de pgAdmin:

```sql
CREATE ROLE blindem WITH LOGIN PASSWORD 'blindem';
CREATE DATABASE blindem      OWNER blindem ENCODING 'UTF8' TEMPLATE template1;
CREATE DATABASE blindem_test OWNER blindem ENCODING 'UTF8' TEMPLATE template1;
```

La base `blindem_test` es la que usan las pruebas (`phpunit.xml` apunta a ella). Si se prefiere otro
nombre o otro usuario, se ajustan `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env` y
`DB_DATABASE` en `phpunit.xml`.

El rol `blindem` es dueño de sus bases, y eso alcanza: las dos extensiones que necesita el esquema
(`btree_gist` para la restricción de contratos solapados y `pg_trgm` para la búsqueda por nombre)
están marcadas como `trusted` en PostgreSQL, o sea que el dueño de la base puede instalarlas sin ser
superusuario. Las instala la migración `2026_10_31_190008_create_postgres_extensions`.

### 4. Esquema y datos

```bash
php artisan migrate --seed
```

Son 23 migraciones: cinco del starter kit y 18 del proyecto. La siembra deja llenas las siete tablas
paramétricas (5 tipos de documento, 6 cargos, 5 tipos de contrato, 7 causales de retiro, 12
conceptos, 5 salarios mínimos de 2022 a 2026 y 18 parámetros legales), las dos cuentas de acceso y
los datos de demostración. Las tablas de negocio —`workers`, `companies`, `contracts`,
`liquidations`— las llena el usuario; la siembra deja además un juego de datos de ejemplo para que
la aplicación no arranque vacía.

### 5. Arranque

```bash
composer run dev
```

Levanta el servidor de aplicación, `vite` para los assets y la cola. La aplicación queda en
`http://localhost:8000`.

Si se prefiere arrancar las piezas por separado, se compilan los assets una vez y se levanta solo el
servidor:

```bash
npm run build
php artisan serve
```

La ruta `/`  redirige al panel (`/dashboard`) cuando hay sesión
abierta y al login cuando no la hay. El panel, atendido por `DashboardController@index`, muestra
agregados reales de la base de datos: contratos activos, liquidables y liquidados, número de
liquidaciones y los cinco contratos pendientes más antiguos.

## Cuentas de prueba

| Perfil        | Correo                     | Contraseña |
| ------------- | -------------------------- | ---------- |
| Administrador | `admin@blindem.test`       | `password` |
| Colaborador   | `colaborador@blindem.test` | `password` |

El colaborador consulta trabajadores y empresas, crea y edita contratos, entra a la pantalla de
liquidación con su historial y exporta a Excel; recibe 403 si intenta crear empresas o trabajadores y
si entra a `/parametricas/*`. El administrador tiene el CRUD completo, incluidas las tablas
paramétricas. El rol se guarda en la columna `role` de `users` y está fuera de `$fillable`, así que
ningún formulario puede asignárselo por relleno masivo: cambiar un rol es una acción explícita, no
un campo más del request.

## Flujos de la aplicación

1. **Contratos** (`Contratos` en el menú): listado con búsqueda por código, trabajador o documento y
   filtro por estado y empresa. Ambos perfiles crean, editan y eliminan contratos; el colaborador
   solo para las empresas que se le asignaron. Un contrato cerrado exige fecha de retiro y causal;
   dos contratos solapados del mismo trabajador se rechazan a nivel de validación y de base (CHECK
   de exclusión PostgreSQL).
2. **Liquidación** (`Liquidaciones`): cada contrato liquidable muestra el botón _Liquidar_. La pantalla
   lista los contratos con tres filtros: estado, tipo de documento (el parámetro de URL es
   `document_type_id`, un id numérico) y número de documento (coincidencia exacta, no parcial). El
   formulario sugiere la variante según el motivo de retiro —bajo el selector una pista recuerda que
   terminación de contrato, renuncia o justa causa usan el formato prestacional, y que despido sin
   justa causa usa el formato con indemnización del artículo 64— y antes de guardar muestra un
   **desglose calculado en vivo**: un efecto con debounce de 350 ms envía el mismo payload del
   guardado a `POST /contratos/{contract}/liquidar/vista-previa` (ruta `liquidations.preview`,
   atendida por `LiquidationController@preview`) con la cabecera `X-XSRF-TOKEN` tomada de la cookie
   `XSRF-TOKEN`, y cancela las peticiones obsoletas con `AbortController`. La vista previa valida con
   el mismo `StoreLiquidationRequest` (fuera de rango devuelve 422 con los errores en JSON), ejecuta
   el mismo motor de cálculo y devuelve `lines`, `totals` (earned/deducted/net), `notices` e
   `indemnization_rule`; no persiste nada. Al guardar, los conceptos quedan congelados con su
   fórmula y se genera el folio `LIQ-AAAA-00001`.
3. **Parámetros** (solo administrador): gestión CRUD de tipos de documento, cargos, tipos de contrato,
   motivos de retiro y salarios mínimos (SMLMV y auxilio por año). Las reglas de cada catálogo —
   singular/plural, unicidad, si admite desactivación y de qué depende su borrado — se declaran en
   `app/Http/Parametrics/ParametricRegistry.php`; el controlador, la validación y las pantallas son
   genéricas. Un catálogo usado por contratos o trabajadores no se borra: se desactiva. Los catálogos
   de conceptos y de parámetros legales vienen sembrados y no tienen pantalla de edición.

## El documento impreso

La pantalla de una liquidación guardada (`/liquidaciones/{liquidation}`) es el documento: encabezado con
trabajador, empresa, cargo y período, las dos bases de cálculo, la tabla de renglones con base, días,
divisor y valor, los tres totales y, si la variante lo incluye, la regla de indemnización aplicada. Cierra
con la declaración de paz y salvo y los dos espacios de firma —trabajador con su documento, empleador con
NIT y razón social—, el mismo texto que escribe `LiquidationWorkbookExporter::signatures()` en el XLSX.

El botón _Imprimir_ llama a `window.print()` y la regla `@media print` de `resources/css/app.css` deja fondo
blanco, apaga las sombras y oculta el menú lateral; lo que no interesa en papel lleva `print:hidden` (la barra
de botones, el desglose de parámetros congelados y la guía normativa). No hay un generador de PDF en el
servidor: se declaró fuera de alcance en `docs/01-requerimientos.md` (RF-06.4) porque duplicar el diseño en
una vista Blade para luego renderizarlo habría significado mantener dos copias sincronizadas a mano.

## Exportación a Excel

El botón de exportación de cada liquidación genera un XLSX con **dos hojas**: "Liquidación", que
replica los bloques y las cinco columnas del formato del cliente, e "Instrucciones y Normativa", con
los cinco pasos para diligenciar el formato, las tres reglas de indemnización del artículo 64
C.S.T. y las fórmulas usadas (cesantías y prima sobre 360, vacaciones sobre 720, intereses de
cesantías del 12 % y salud y pensión del 4 %). El contenido de esa segunda hoja sale de
`app/Http/Presenters/LiquidationGuidePresenter.php`, que es la única fuente tanto del Excel como de
los bloques desplegables `<details>` de las pantallas de liquidación; al final de
`LiquidationWorkbookExporter::guideSheet()` se llama a `setActiveSheetIndex(0)` para que el libro
abra siempre en la liquidación.

Los archivos se generan en `storage/app/private/exports`, fuera del alcance público, y se sirven
como descarga con `deleteFileAfterSend()`: el temporal se borra apenas termina la respuesta. El
exportador recorre una vez los renglones guardados; su coste es O(n) en el número de conceptos de la
liquidación.

## Pruebas

```bash
php artisan test
```

La suite pasa en verde: **255 pruebas con 1153 aserciones** en PHPUnit 12. Hay pruebas de dominio
(`tests/Unit`, sin abrir conexión), pruebas de integración y de flujo (`tests/Feature`, contra
`blindem_test`) y pruebas "golden" que fijan los números exactos del Excel del cliente: 7.688.130 de
devengado con indemnización, 73.204 de deducciones y 7.614.926 de neto en el formato con
indemnización; 3.254.103, 73.204 y 3.180.899 en el prestacional; y renglón a renglón 1.075.000 de
cesantías, 64.500 de intereses, 39.602 de vacaciones con 15 días, 875.453 de salario pendiente,
124.548 de auxilio de transporte y 4.434.027 de indemnización, entre otros. Si alguien mueve una
fórmula, un divisor o el orden de una multiplicación, estas pruebas lo atrapan al peso.

El análisis estático corre con PHPStan nivel 7 (larastan) sin errores y el estilo con Pint, preset
`laravel`.

El suite usa la base `blindem_test` y construye su propio esquema: si la base está vacía, la primera
prueba corre las migraciones solas. `tests/Unit` no abre conexión; `tests/Feature` sí, porque buena
parte de lo que se quiere comprobar son reglas que impone el motor de la base y no PHP.

Entre esas reglas están las que revisa `tests/Feature/DatabaseIntegrityTest.php`: dos contratos
solapados del mismo trabajador, un salario en cero, un rango de fechas invertido, un `nit` vacío, un
documento duplicado, una llave foránea inexistente y dos correos que solo difieren en mayúsculas.
Ninguna pasa.

## Documentación de API (Swagger/OpenAPI)

Toda la aplicación está documentada con OpenAPI 3.1 usando `zircote/swagger-php`. La especificación
vive como atributos PHP en `app/OpenApi/` (una carpeta por módulo, esquemas de dominio en
`app/OpenApi/Schemas/`) y se sirve así:

| URL                            | Qué entrega                                                       |
| ------------------------------ | ----------------------------------------------------------------- |
| `/documentacion/api`           | Swagger UI (consola OpenAPI 3.1 con todos los endpoints)          |
| `/documentacion/api/spec.json` | Especificación OpenAPI 3.1 generada en vivo (`ApiDocsController`) |

La generación es programática: `ApiDocsController::build()` recorre `app/OpenApi/` con
`OpenApi\Builder` y responde JSON; `tests/Feature/ApiDocsTest.php` verifica que la especificación
exponga los paths de todos los módulos (contratos, liquidaciones, empresas, trabajadores,
paramétricas y configuración de la cuenta) y que cada `RequestBody` tenga su esquema definido.

Los endpoints son los de una SPA Inertia: un `GET` entrega la página HTML y, con el header
`X-Inertia`, el mismo JSON `{ component, props }` que monta React; ambos formatos están documentados
con ejemplos reales por endpoint (incluido el payload completo de una liquidación con sus trazas de
cálculo y la respuesta binaria de la exportación a Excel). La autenticación documentada es la de la
propia aplicación: cookie de sesión (`WebSession`) más header `X-CSRF-TOKEN` (`CsrfHeader`) en las
peticiones de escritura.

## Estructura

```
app/
├── Domain/                  lógica de negocio, sin dependencias de Laravel
│   ├── Contracts/           interfaces que el HTTP consume (puertos)
│   ├── Enum/                16 enums, uno por cada lista cerrada de la base
│   ├── Exceptions/          errores de dominio
│   └── ValueObjects/        Money, WageSet, ConceptDefinition, ConceptList
├── Infrastructure/
│   └── Persistence/
│       └── Eloquent/        adaptadores: traducen modelos en objetos de dominio
├── Http/                    controladores, solicitudes, políticas
├── Models/                  un modelo por tabla: relleno, tipos y relaciones
└── Providers/               AppServiceProvider enlaza puertos con adaptadores
database/
├── migrations/              esquema relacional
├── seeders/                 datos paramétricos
└── factories/
resources/js/                React + TypeScript sobre Inertia, Tailwind
tests/
```

La regla de dependencia es de una sola dirección: `Http` conoce `Domain`, `Domain` no conoce `Http`
ni `Eloquent`. `Infrastructure` es el único lugar donde el dominio toca la base. Por eso el motor de
cálculo se prueba sin levantar la aplicación y por eso cambiar PostgreSQL por otro motor no movería
una línea de cálculo.

## El esquema

Es relacional y las reglas viven en la base, no solo en el formulario:

- 26 tablas con llaves foráneas y borrado lógico donde se necesita historial.
- 46 restricciones `CHECK` con nombre (`ck_c_salary`, `ck_liq_math`, `ck_worker_gender`), así el
  error dice qué regla se rompió en lugar de un número anónimo.
- `EXCLUDE USING gist` en `contracts`: un trabajador no puede tener dos contratos vigentes en el
  mismo rango de fechas. El predicado `WHERE (deleted_at IS NULL)` deja registrar el historial de un
  contrato cerrado.
- Índice único funcional sobre `lower(email)` en `users`.
- Índice GIN con `gin_trgm_ops` para buscar trabajadores por un fragmento del nombre.
- `NUMERIC(14,2)` en los montos; PHP los lee como cadena y nunca como flotante.
- `liquidation_items` guarda copia del concepto y `liquidations` guarda una fotografía `jsonb` de los
  parámetros usados: un documento firmado no cambia si mañana se edita el catálogo.

## Parámetros legales

Los porcentajes, los días y los divisores no están escritos en el código. Viven en `legal_parameters`
(clave, valor, unidad y vigencia) y en `minimum_wages` (salario mínimo y auxilio de transporte por
año). Cambiar el porcentaje de intereses de cesantías o el salario mínimo del año siguiente es editar
una fila desde la interfaz de administrador, no desplegar una nueva versión. Cada liquidación guarda
además la copia de los valores con los que se calculó.

## Rutas principales

| Ruta                                          | Nombre                          | Qué hace                                                   |
| --------------------------------------------- | ------------------------------- | ---------------------------------------------------------- |
| `/`                                           | `home`                          | Redirige al panel con sesión abierta, al login sin ella    |
| `/dashboard`                                  | `dashboard`                     | Panel con agregados reales de la base                      |
| `/contratos` (+ `crear`, `{contract}/editar`) | `contracts.*`                   | CRUD completo de contratos                                 |
| `/liquidaciones`                              | `liquidations.index`            | Listado con filtros por estado, tipo y número de documento |
| `/contratos/{contract}/liquidar`              | `liquidations.create` / `store` | Formulario de liquidación y guardado                       |
| `/contratos/{contract}/liquidar/vista-previa` | `liquidations.preview`          | Cálculo en vivo en JSON, sin persistir                     |
| `/liquidaciones/{liquidation}`                | `liquidations.show`             | Documento guardado con su desglose                         |
| `/liquidaciones/{liquidation}/excel`          | `liquidations.excel`            | Descarga del XLSX de dos hojas                             |
| `/empresas`, `/trabajadores`                  | `companies.*`, `workers.*`      | Consulta para ambos perfiles; CRUD solo administrador      |
| `/parametricas/{recurso}`                     | `parametrics.*`                 | CRUD de las cinco tablas paramétricas, solo administrador  |
| `/documentacion/api`                          | `api-docs.ui`                   | Swagger UI con todos los endpoints                         |
| `/documentacion/api/spec.json`                | `api-docs.spec`                 | Especificación OpenAPI 3.1 generada en vivo                |

## Documentación del proyecto

La carpeta `docs/` amplía lo que este README resume:

- [docs/01-requerimientos.md](docs/01-requerimientos.md) — requerimientos funcionales y no
  funcionales, perfiles, reglas de negocio tomadas del Excel del cliente, alcance y criterios de
  aceptación.
- [docs/02-diagrama-de-clases.md](docs/02-diagrama-de-clases.md) — diagrama de clases (mermaid) y
  explicación de cada clase del dominio y de la infraestructura.
- [docs/03-diseno.md](docs/03-diseno.md) — decisiones de arquitectura, flujo de una liquidación de
  extremo a extremo, modelo de datos, seguridad, exportación, pruebas, rendimiento y despliegue.
