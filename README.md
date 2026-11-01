# Blindem — Liquidadora de prestaciones sociales

Aplicación web para registrar trabajadores, empresas y contratos, y calcular la liquidación de
prestaciones sociales de un trabajador en Colombia: cesantías, intereses sobre cesantías, prima de
servicios, vacaciones, salario pendiente, auxilio de transporte, horas extras y descuentos de salud y
pensión, con la indemnización cuando el contrato termina sin justa causa.

Cada cálculo sale de un motor independiente que recibe el contrato y los parámetros legales
vigentes; ningún número se escribe a mano en la interfaz. Los montos se manejan con aritmética de
precisión (`bcmath`) y escalas fijas, de forma que el resultado en pantalla coincide con el del
documento exportable, al peso.

## Requisitos

| Componente | Versión usada                                                              |
| ---------- | -------------------------------------------------------------------------- |
| PHP        | 8.5 (el `composer.json` pide `^8.3`), con `bcmath` y `pdo_pgsql` activadas |
| Composer   | 2.x                                                                        |
| Node       | 24 (con npm)                                                               |
| PostgreSQL | 18.6                                                                       |

`bcmath` no es opcional: el motor de dinero llama funciones `bc*` para sumar y dividir montos sin
enterarse de los errores del punto flotante.

## Instalación

### 1. Código y dependencias

```bash
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
paramétricas (5 tipos de documento, 6 cargos, 5 tipos de contrato, 7 causales de retiro, 10
conceptos, 5 salarios mínimos de 2022 a 2026 y 18 parámetros legales) y las dos cuentas de acceso.
Las tablas de negocio —`workers`, `companies`, `contracts`, `liquidations`— quedan vacías: esas las
llena el usuario.

### 5. Arranque

```bash
composer run dev
```

Levanta el servidor de aplicación, `vite` para los assets y la cola. La aplicación queda en
`http://localhost:8000`.

## Cuentas de prueba

| Perfil        | Correo                     | Contraseña |
| ------------- | -------------------------- | ---------- |
| Administrador | `admin@blindem.test`       | `password` |
| Colaborador   | `colaborador@blindem.test` | `password` |

El colaborador registra trabajadores, empresas y contratos, consulta las liquidaciones y exporta. El
administrador además edita las tablas paramétricas. El rol se guarda en la columna `role` de `users`
y está fuera de `$fillable`, así que ningún formulario puede asignárselo por relleno masivo: cambiar
un rol es una acción explícita, no un campo más del request.

## Flujos de la aplicación

1. **Contratos** (`Contratos` en el menú): listado con búsqueda por código, trabajador o documento y
   filtro por estado y empresa. Ambos perfiles crean, editan y eliminan contratos; el colaborador
   solo para las empresas que se le asignaron. Un contrato cerrado exige fecha de retiro y causal;
   dos contratos solapados del mismo trabajador se rechazan a nivel de validación y de base (CHECK
   de exclusión PostgreSQL).
2. **Liquidación** (`Liquidaciones`): cada contrato liquidable muestra el botón *Liquidar*. La pantalla
   filtrada por estado, tipo de documento y número de documento cumple el formato estándar, guarda los
   conceptos congelados con su fórmula y genera el folio `LIQ-AAAA-00001`; el XLSX sale con la
   exportación por liquidación.
3. **Parámetros** (solo administrador): gestión CRUD de tipos de documento, cargos, tipos de contrato,
   motivos de retiro y salarios mínimos (SMLMV y auxilio por año). Las reglas de cada catálogo —
   singular/plural, unicidad, si admite desactivación y de qué depende su borrado — se declaran en
   `app/Http/Parametrics/ParametricRegistry.php`; el controlador, la validación y las pantallas son
   genéricas. Un catálogo usado por contratos o trabajadores no se borra: se desactiva.

## Pruebas

```bash
php artisan test
```

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

| URL                          | Qué entrega                                                    |
|------------------------------|----------------------------------------------------------------|
| `/documentacion/api`         | Swagger UI (consola OpenAPI 3.1 con todos los endpoints)        |
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
