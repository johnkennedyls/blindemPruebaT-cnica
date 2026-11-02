# Documento 2 — Diagrama de clases

**Proyecto:** Sistema de Liquidación de Prestaciones Sociales · Laravel 13 + React (Inertia)

> Este documento tiene tres partes.
> **Parte A** enseña a leer diagramas de clases (2 minutos, para quien nunca los ha visto).
> **Parte B** es el diagrama simplificado: el que hay que tener en la cabeza.
> **Parte C** es el diagrama completo para el README, con la explicación clase por clase.

---

## PARTE A — Cómo se lee un diagrama de clases (nivel junior)

Un diagrama de clases es **un mapa de las "plantillas" (clases) del programa y cómo se conectan**.
Una clase es un molde que tiene: datos (atributos) y acciones (métodos). En Laravel, los modelos Eloquent,
los Services y los FormRequests **son** clases: el diagrama solo las dibuja y muestra quién llama a quién.

### Los símbolos

| Lo que se ve                  | Significa                                                                          | En el proyecto                               |
| ----------------------------- | ---------------------------------------------------------------------------------- | -------------------------------------------- |
| Una caja con 3 compartimentos | Una **clase**: nombre / atributos / métodos                                        | `Contract`                                   |
| `<<interface>>` encima        | Un **contrato**: dice "toda clase que prometa esto debe implementar estos métodos" | `ConceptCalculator`                          |
| `<<abstract>>`                | Clase base incompleta, no se instancia                                             | —                                            |
| `<<enumeration>>`             | Lista cerrada de valores posibles                                                  | `ContractStatus`                             |
| `+`                           | `public` — se puede llamar desde fuera                                             | `+calculate()`                               |
| `-`                           | `private` — solo dentro de la clase                                                | `-days`                                      |
| `#`                           | `protected` — solo en la clase y sus hijas                                         |                                              |
| Línea recta `——`              | Relación simple / asociación                                                       |                                              |
| Flecha hueca `◁──`            | **Herencia / realización** ("es un")                                               | `CesantiasCalculator ◁── ConceptCalculator`  |
| Rombo negro `◆──`             | **Composición** ("contiene y no sobrevive sin ello")                               | `Liquidation ◆── LiquidationItem`            |
| Rombo blanco `◇──`            | **Agregación** ("contiene, pero puede existir separado")                           |                                              |
| Flecha normal `──▶`           | Dependencia / "usa a"                                                              | `LiquidationEngine ──▶ WorkedDaysCalculator` |
| `1` `0..*` `1..*`             | **Multiplicidad**: cuántos                                                         | Un contrato → `0..*` liquidaciones           |

### Las tres capas (la analogía del restaurante)

```
 ┌─────────────────────────────────────────────────────────┐
 │  CONTROLADOR  =  el mesero                                │
 │  Recibe el pedido (HTTP), lo trae a la cocina,            │
 │  devuelve el plato. NO cocina. NO sabe de recetas.         │
 ├─────────────────────────────────────────────────────────┤
 │  SERVICE / MOTOR  =  el chef                              │
 │  Conoce la receta y ejecuta. Aquí viven las fórmulas.     │
 │  No sabe que existe un navegador ni un formulario.        │
 ├─────────────────────────────────────────────────────────┤
 │  MODELO (Eloquent)  =  la despensa                        │
 │  Guarda y ordena los ingredientes (filas de la BD).       │
 │  No toma decisiones de negocio.                           │
 └─────────────────────────────────────────────────────────┘
```

**La regla de oro que evita el 90 % de los errores de junior:**

> ❌ Mal: `LiquidationController@calcular` con 120 líneas de fórmulas adentro, leyendo `$request->salario`.
> ✅ Bien: el controlador son 8 líneas y llama a `LiquidationEngine->calculate(...)`.
>
> **¿Por qué?** Porque así se puede probar las fórmulas sin navegador, sin login y sin base de datos,
> y cuando el cliente cambie el 12 % por un 10 % solo se toca un archivo.

---

## PARTE B — El diagrama que hay que tener en la cabeza

Este es el 20 % del diagrama que explica el 80 % del sistema. Si solo se memoriza uno, que sea este.

```mermaid
classDiagram
    direction LR

    class Contract {
        +date start_date
        +date end_date
        +decimal base_salary_monthly
        +decimal transport_auxiliary_monthly
        +decimal overtime_average_monthly
        +ContractType contractType
        +TerminationReason reason
    }

    class LiquidationEngine {
        <<Chef>>
        +calculate(LiquidationDraft) LiquidationResult
    }

    class SalaryBaseBuilder {
        {static} +of(LiquidationDraft, WageSet, Parameters) SalaryBaseAssembly
    }

    class WorkedDaysCalculator {
        {static} +commercialFromYearStart(string date) int
        {static} +commercialFromSemesterStart(string date) int
        {static} +naturalBetween(string start, string end) int
        {static} +remainingBetween(string from, string to) int
    }

    class ConceptCalculator {
        <<interface>>
        +key() string
        +compute(LiquidationContext) LiquidationLine
    }

    class IndemnizationStrategy {
        <<interface>>
        +rule() IndemnizationRule
        +applies(LiquidationContext) bool
        +days(LiquidationContext) IndemnizableDays
    }

    class LiquidationResult {
        +LiquidationLine[] lines
        +Money earned
        +Money deducted
        +Money net
        +array notices
    }

    class LiquidationLine {
        +ConceptDefinition concept
        +Money base
        +string quantity
        +int divisor
        +string formulaText
        +Money amount
    }

    Contract --> LiquidationEngine : entra
    LiquidationEngine --> SalaryBaseBuilder : calcula bases
    LiquidationEngine --> WorkedDaysCalculator : calcula días
    LiquidationEngine --> ConceptCalculator : delega cada renglón
    LiquidationEngine --> IndemnizationStrategy : si aplica
    LiquidationEngine --> LiquidationResult : sale
    LiquidationResult o-- LiquidationLine : renglones
```

**Lectura en español:** _"El contrato entra al motor. El motor le pregunta al calculador de días cuántos días
hay, al constructor de bases cuál es la base prestacional y cuál la vacacional, y a cada calculadora de
concepto cuánto vale su renglón. Devuelve un resultado con renglones y tres totales."_

Eso es todo el corazón del sistema. Lo demás (usuarios, roles, React, Excel) es alrededor.

---

## PARTE C — Diagrama de clases completo

> **Estado real del código.** Este diagrama es el mapa de lo que quedó construido, con las dos
> excepciones que se dicen abajo. En `app/` están los 17 modelos Eloquent, uno por tabla de negocio y
> uno por catálogo (`ActivityLog`, `Company`, `ConceptCatalog`, `Contract`, `ContractType`,
> `DocumentType`, `LegalParameter`, `Liquidation`, `LiquidationFile`, `LiquidationItem`, `MinimumWage`,
> `OvertimeRecord`, `Position`, `TerminationReason`, `User`, `UserCompany`, `Worker`); los cuatro
> puertos `ConceptCatalogRepository`, `LegalParameterRepository`, `LiquidationRepository` y
> `MinimumWageRepository` (`app/Domain/Contracts`) con sus adaptadores `Eloquent{...}Repository`
> (`app/Infrastructure/Persistence/Eloquent`), enlazados como `singleton` en
> `AppServiceProvider::register()`; el motor completo (`LiquidationEngine`, `SalaryBaseBuilder`,
> `WorkedDaysCalculator`, `Parameters`), las nueve calculadoras de concepto y las cinco clases de
> indemnización; los presentadores `WorkerPresenter`, `CompanyPresenter`, `ContractPresenter`,
> `LiquidationPresenter` y `LiquidationGuidePresenter`; el exportador `LiquidationWorkbookExporter`; el
> mapeador `LiquidationDraftMapper` (`app/Application`); y los controladores de empresas, trabajadores,
> contratos, liquidaciones, paramétricas, panel y documentación de API, sostenidos por las políticas
> `WorkerPolicy`, `CompanyPolicy`, `ContractPolicy`, `LiquidationPolicy` y el middleware
> `EnsureUserIsAdmin`. Consultar esas tablas es infraestructura, así que vive en los adaptadores: los
> modelos no publican consultas estáticas tipo `active()` o `forYear()`, y los cast de dinero y fechas
> están declarados en cada modelo.
>
> Lo que **no** se escribió son puertos y adaptadores propios para `Worker`, `Company` y `Contract`:
> esos tres se leen con Eloquent desde el controlador. No llevan regla de negocio encima, y envolverlos
> habría añadido dos archivos por entidad sin ganar nada. El puente que sí existía entre la fila de
> `contracts` y el motor es `LiquidationDraftMapper`, y ese sí está construido. Tampoco se construyó un
> generador de PDF: el documento imprimible es la propia pantalla `pages/liquidations/show.tsx`, que cierra
> con la declaración de paz y salvo y los dos bloques de firma, y la hoja de estilos `@media print` se encarga
> del resto. La pantalla de bitácora tampoco existe: la tabla `activity_logs` se llena, la vista no.

### C.1 Diagrama

```mermaid
classDiagram
    direction TB

    %% ============ CAPA 1 : PRESENTACIÓN ============
    class LoginController {
        +create() View
        +store(LoginRequest) Redirect
        +destroy() Redirect
    }
    class WorkerController {
        +index(Request) View
        +create() View
        +store(StoreWorkerRequest) Redirect
        +show(Worker) View
        +edit(Worker) View
        +update(UpdateWorkerRequest, Worker) Redirect
        +destroy(Worker) Redirect
    }
    class CompanyController {
        +index(Request) View
        +store(StoreCompanyRequest) Redirect
        +update(UpdateCompanyRequest, Company) Redirect
        +destroy(Company) Redirect
    }
    class ContractController {
        +index(ContractIndexRequest) View
        +create(Request) Response
        +store(StoreContractRequest) RedirectResponse
        +edit(Contract) Response
        +update(UpdateContractRequest, Contract) RedirectResponse
        +destroy(Contract) RedirectResponse
    }
    class LiquidationController {
        +index(Request) Response
        +create(Request, Contract) Response
        +preview(StoreLiquidationRequest, Contract) JsonResponse
        +store(StoreLiquidationRequest, Contract) RedirectResponse
        +show(Request, Liquidation) Response
        +excel(Liquidation, LiquidationWorkbookExporter) BinaryFileResponse
    }
    class ParametricController {
        +index(Request, string resource) Response
        +create(Request, string resource) Response
        +store(ParametricRequest, string resource) RedirectResponse
        +edit(Request, string resource) Response
        +update(ParametricRequest, string resource) RedirectResponse
        +destroy(Request, string resource) RedirectResponse
    }
    class ParametricRegistry {
        <<Service>>
        +find(string slug) ?ParametricDefinition
        +all() ParametricDefinition[]
    }
    class ParametricDefinition {
        <<value object>>
        +string slug
        +string title
        +ParametricField[] fields
    }
    class EnsureUserIsAdmin {
        <<middleware>>
        +handle(Request, Closure) Response
    }
    class DashboardController {
        +index() View
    }

    %% ============ CAPA 2 : NEGOCIO / MOTOR ============
    class LiquidationEngine {
        <<Service>>
        +calculate(LiquidationDraft) LiquidationResult
        -calculatorFor(ConceptDefinition) ?ConceptCalculator
        -remainingTermDays(LiquidationDraft) int
    }
    class WorkedDaysCalculator {
        <<Service>>
        {static} +commercialFromYearStart(string date) int
        {static} +commercialFromSemesterStart(string date) int
        {static} +naturalBetween(string start, string end) int
        {static} +remainingBetween(string from, string to) int
    }
    class SalaryBaseBuilder {
        <<Service>>
        +of(LiquidationDraft, WageSet, Parameters) SalaryBaseAssembly
    }
    class SalaryBaseAssembly {
        <<dto>>
        +SalaryBases bases
        +array notices
    }
    class IndemnizationStrategyFactory {
        <<Service>>
        +make(IndemnizationRule, ContractModality, Money basic, Money smlmv, Parameters) IndemnizationStrategy
    }
    class LiquidationDraftMapper {
        <<Application>>
        +fromContract(Contract, string when, LiquidationVariant, int, int, array, ?int, ?int) LiquidationDraft
        +suggestsVariant(Contract) LiquidationVariant
    }

    %% ============ CAPA 2b : ESTRATEGIAS DE CONCEPTO ============
    class ConceptCalculator {
        <<interface>>
        +key() string
        +computes(LiquidationContext, ComputedLines, ConceptDefinition) bool
        +compute(LiquidationContext, ComputedLines, ConceptDefinition) Computation
    }
    class CesantiasCalculator
    class InteresesCesantiasCalculator
    class PrimaCalculator
    class VacacionesCalculator
    class SalarioPendienteCalculator
    class AuxilioTransporteCalculator
    class SocialSecurityContributionCalculator
    class ManualValueCalculator
    class IndemnizacionCalculator

    class IndemnizationStrategy {
        <<interface>>
        +rule() IndemnizationRule
        +applies(LiquidationContext) bool
        +days(LiquidationContext) IndemnizableDays
        +formulaText(LiquidationContext) string
    }
    class ProportionalByYears {
        <<abstract>>
        -IndemnizationRule rule
        -string firstYearKey
        -string perYearKey
        +applies() true
        +days(LiquidationContext) IndemnizableDays
        +formulaText() string
    }
    class IndefinidoMenos10Smlmv {
        <<final>>
        firstYearKey = indemnization_first_year_days_low
        perYearKey = indemnization_per_year_days_low
    }
    class IndefinidoMas10Smlmv {
        <<final>>
        firstYearKey = indemnization_first_year_days_high
        perYearKey = indemnization_per_year_days_high
    }
    class TerminoFijoPlazoRestante {
        <<final>>
    }
    class SinIndemnizacion {
        <<final>>
    }

    %% ============ CAPA 3 : VALORES Y DTOs ============
    class Money {
        <<value object>>
        -string amount
        +of(mixed valor) Money
        +zero() Money
        +times(string|int factor) Money
        +dividedBy(string|int divisor) Money
        +plus(Money otra) Money
        +minus(Money otra) Money
        +rounded() Money
        +amount() string
        +exactAmount() string
    }
    class WageSet {
        <<value object>>
        +int year
        +Money minimumWage
        +Money transportAuxiliary
    }
    class SalaryBases {
        <<dto>>
        +Money basic
        +Money transport
        +Money variables
        +Money prestacional
        +Money vacacional
    }
    class DayCounts {
        <<dto>>
        +int commercialYear
        +int commercialSemester
        +int naturalTotal
        +int cesantias
        +int prima
        +int vacaciones
        +int pendingSalary
    }
    class Tenure {
        <<value object>>
        +int totalDays
        +int completeYears
        +int fractionDays
        +int yearDays
        {static} +fromTotalDays(int totalDays, int yearDays) self
        +isUnderOneYear() bool
    }
    class IndemnizableDays {
        <<dto>>
        +Money firstYear
        +Money followingYears
        +Money fraction
        +Money total
        +string text
    }
    class Parameters {
        <<dto>>
        -LegalParameterRepository repository
        -string asOfDate
        +asOfDate() string
        +days(string key) int
        +rate(string key) Money
        +money(string key) Money
    }
    class Computation {
        <<dto>>
        +Money base
        +string quantity
        +int divisor
        +string formulaText
        +Money amount
    }
    class ComputedLines {
        <<dto>>
        +empty() ComputedLines
        +with(ConceptDefinition, LiquidationLine) ComputedLines
        +valueOf(string code) ?Money
    }
    class LiquidationContext {
        <<dto>>
        +LiquidationDraft draft
        +SalaryBases bases
        +DayCounts days
        +Tenure tenure
        +WageSet wages
        +Parameters parameters
        +IndemnizationStrategy strategy
        +int remainingTermDays
        +variant() LiquidationVariant
        +dailyWage() Money
    }
    class LiquidationDraft {
        <<dto>>
        +LiquidationVariant variant
        +int pendingVacationDays
        +int pendingSalaryDays
        +bool forceIndemnization
        +array extraDeductions
    }
    class LiquidationLine {
        <<dto>>
        +string conceptCode
        +string conceptName
        +ConceptSide side
        +Money base
        +decimal quantity
        +int divisor
        +string formulaText
        +Money amount
        +bool appliesSocialSecurity
    }
    class LiquidationResult {
        <<dto>>
        +array lines
        +Money totalEarned
        +Money totalDeductions
        +Money net
        +LiquidationContext context
    }

    %% ============ CAPA 4 : MODELOS ELOQUENT ============
    class User {
        +int id
        +string name
        +string email
        +Role role
        +contracts() HasMany
    }
    class DocumentType {
        +int id
        +string code
        +string name
        +string abbreviation
        +workers() HasMany
    }
    class Worker {
        +int id
        +int document_type_id
        +string document_number
        +string first_names
        +string last_names
        +fullName() string
        +contracts() HasMany
    }
    class Company {
        +int id
        +string nit
        +string business_name
        +string trade_name
        +contracts() HasMany
    }
    class Position {
        +int id
        +string code
        +string name
        +contracts() HasMany
    }
    class ContractType {
        +int id
        +string code
        +string name
        +ContractModality modality
        +bool requires_scheduled_end
    }
    class TerminationReason {
        +int id
        +string code
        +string description
        +TerminationClassification classification
        +bool applies_indemnization
        +IndemnizationRule rule
    }
    class Contract {
        +int id
        +int worker_id
        +int company_id
        +int contract_type_id
        +int position_id
        +int termination_reason_id
        +date start_date
        +date end_date
        +date scheduled_end_date
        +decimal base_salary_monthly
        +decimal transport_auxiliary_monthly
        +decimal overtime_average_monthly
        +ContractStatus status
        +worker() BelongsTo
        +company() BelongsTo
        +liquidations() HasMany
    }
    class Liquidation {
        +int id
        +string folio
        +int contract_id
        +int created_by
        +LiquidationVariant variant
        +LiquidationStatus status
        +decimal base_prestacional_snapshot
        +decimal base_vacacional_snapshot
        +decimal smlmv_snapshot
        +decimal total_earned
        +decimal total_deductions
        +decimal net_total
        +array params_snapshot
        +contract() BelongsTo
        +items() HasMany
    }
    class LiquidationItem {
        +int id
        +int liquidation_id
        +string concept_code
        +ConceptSide side
        +decimal calculation_base
        +decimal quantity
        +int divisor
        +string formula_text
        +decimal amount
        +int sequence
    }
    class ConceptCatalog {
        +int id
        +string code
        +string name
        +ConceptSide side
        +string formula_key
        +int default_divisor
        +bool applies_ss_contribution
        +bool is_system
    }
    class MinimumWage {
        +int year
        +decimal smlmv
        +decimal transport_auxiliary
    }
    class LegalParameter {
        +string key
        +decimal numeric_value
        +date effective_from
        +date effective_to
    }

    %% ============ CAPA 5 : PERSISTENCIA Y SALIDA ============
    class LiquidationRepository {
        <<Service>>
        +save(LiquidationResult, Contract, User) Liquidation
        +find(int id) Liquidation
        +void_(Liquidation, User)
    }
    class LiquidationWorkbookExporter {
        <<Service>>
        +filename(Liquidation) string
        +export(Liquidation) string
        +book(Liquidation) Spreadsheet
        -heading(sheet, bool) int
        -section(sheet, row, title) int
        -pair(sheet, row, ...) int
        -tableHeader(sheet, row, titles) int
        -conceptRow(sheet, row, item, labels) int
        -indemnizationBlock(sheet, row, item, snapshot, smlmv) array
        -totalRow(sheet, row, title, value, bool) int
        -signatures(sheet, row, doc, nit) void
        -guideSheet(Spreadsheet) void
        -guideDetail(sheet, row, text) void
    }
    class LiquidationGuidePresenter {
        <<Presenter>>
        +sections() array
    }
    class ActivityLogger {
        <<Service>>
        +log(string action, $subject, array changes, User actor)
    }

    %% ============ ENUMS ============
    class Role {
        <<enumeration>>
        Administrator
        Collaborator
    }
    class ContractModality {
        <<enumeration>>
        Indefinido
        TerminoFijo
        ObraOLabor
        Aprendizaje
        Ocasional
        PrestacionServicios
    }
    class ContractStatus {
        <<enumeration>>
        Activo
        Terminado
        EnLiquidacion
        Liquidado
    }
    class LiquidationVariant {
        <<enumeration>>
        Prestacional
        ConIndemnizacion
    }
    class LiquidationStatus {
        <<enumeration>>
        Borrador
        Calculada
        Aprobada
        Anulada
    }
    class ConceptSide {
        <<enumeration>>
        Earning
        Deduction
    }

    %% ============ POLICIES ============
    class ContractPolicy {
        +viewAny(User) bool
        +create(User) bool
        +update(User, Contract) bool
        +delete(User, Contract) bool
    }
    class LiquidationPolicy {
        +view(User, Liquidation) bool
    }

    %% ============ RELACIONES ENTRE MODELOS ============
    DocumentType "1" --> "0..*" Worker : clasifica
    Worker "1" --> "0..*" Contract : celebra
    Company "1" --> "0..*" Contract : contrata
    Position "1" --> "0..*" Contract : desempeña
    ContractType "1" --> "0..*" Contract : regula
    TerminationReason "1" --> "0..*" Contract : motiva
    Contract "1" *-- "0..*" Liquidation : genera
    Liquidation "1" *-- "1..*" LiquidationItem : se desglosa en
    ConceptCatalog "1" --> "0..*" LiquidationItem : tipifica
    User "1" --> "0..*" Liquidation : crea
    Contract --> ContractStatus : tiene
    ContractType --> ContractModality : usa
    TerminationReason --> LiquidationVariant : sugiere
    Liquidation --> LiquidationVariant : es de tipo
    Liquidation --> LiquidationStatus : en estado

    %% ============ ALAMBRE: CONTROLLER → ENGINE ============
    LiquidationController --> LiquidationEngine : pide el cálculo
    LiquidationController --> LiquidationRepository : guarda
    LiquidationController --> LiquidationWorkbookExporter : exporta
    LiquidationController --> LiquidationGuidePresenter : muestra la guía
    LiquidationController --> LiquidationPolicy : autoriza
    ParametricController --> ParametricRegistry : lee la definición del catálogo
    WorkerController --> WorkerPolicy : autoriza
    CompanyController --> CompanyPolicy : autoriza
    ContractController --> ContractPolicy : autoriza
    ContractController --> Contract
    DashboardController --> Contract : cuenta

    %% ============ ALAMBRE: ENGINE → SERVICIOS ============
    LiquidationEngine --> WorkedDaysCalculator
    LiquidationEngine --> SalaryBaseBuilder
    LiquidationEngine --> MinimumWageRepository : lee SMLMV
    LiquidationEngine --> LegalParameterRepository : lee parámetros
    LiquidationEngine --> ConceptCatalogRepository : lee los conceptos activos
    LiquidationEngine --> IndemnizationStrategyFactory
    LiquidationEngine --> "9" ConceptCalculator : recorre
    LiquidationEngine ..> LiquidationContext : construye
    LiquidationEngine ..> LiquidationResult : devuelve
    LiquidationEngine ..> LiquidationDraft : recibe
    SalaryBaseBuilder --> WageSet : entra
    SalaryBaseBuilder --> Parameters : valida tope del auxilio
    WorkedDaysCalculator --> DayCounts : alimenta
    WorkedDaysCalculator --> Tenure : sale
    Parameters --> LegalParameterRepository : pide por clave y vigencia
    SalaryBaseBuilder --> SalaryBaseAssembly : sale
    SalaryBaseAssembly --> SalaryBases : contiene
    IndemnizationStrategyFactory --> IndemnizationStrategy : elige
    IndemnizacionCalculator --> IndemnizationStrategy : usa
    ConceptCalculator <|.. CesantiasCalculator
    ConceptCalculator <|.. InteresesCesantiasCalculator
    ConceptCalculator <|.. PrimaCalculator
    ConceptCalculator <|.. VacacionesCalculator
    ConceptCalculator <|.. SalarioPendienteCalculator
    ConceptCalculator <|.. AuxilioTransporteCalculator
    ConceptCalculator <|.. SocialSecurityContributionCalculator
    ConceptCalculator <|.. ManualValueCalculator
    ConceptCalculator <|.. IndemnizacionCalculator
    IndemnizationStrategy <|.. ProportionalByYears
    ProportionalByYears <|-- IndefinidoMenos10Smlmv
    ProportionalByYears <|-- IndefinidoMas10Smlmv
    IndemnizationStrategy <|.. TerminoFijoPlazoRestante
    IndemnizationStrategy <|.. SinIndemnizacion
    LiquidationRepository --> Liquidation : persiste
    LiquidationRepository --> ActivityLogger : audita
    LiquidationWorkbookExporter --> Liquidation : lee
    LiquidationWorkbookExporter --> LiquidationGuidePresenter : hoja de instrucciones
    LiquidationGuidePresenter --> LiquidationController : texto de los desplegables
```

### C.2 Explicación clase por clase

#### Capa 1 — Presentación (los controladores)

| Clase                   | Qué hace                                                                                                                                                  | Regla práctica                                                                                                      |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `LoginController`       | Muestra y procesa `/login`.                                                                                                                               | Se apoya en el `Auth` de Laravel, sin escribir hashing a mano.                                                      |
| `WorkerController`      | CRUD de trabajadores.                                                                                                                                     | Solo valida, guarda y redirige.                                                                                     |
| `CompanyController`     | CRUD de empresas (solo admin).                                                                                                                            |                                                                                                                     |
| `ContractController`    | CRUD de contratos: listado con búsqueda y filtros, crear, editar, actualizar y eliminar.                                                                  | Aquí es donde la tentación de poner fórmulas es más grande. **No conviene.**                                        |
| `LiquidationController` | Orquesta: arma el borrador, pide el cálculo al motor, muestra, guarda y exporta. `preview()` es el mismo camino devuelto en JSON y sin escribir nada.     | Debe tener métodos cortos. Si `store()` pasa de 15 líneas, algo se quedó adentro.                                   |
| `ParametricController`  | Un solo controlador para los cinco catálogos editables. No conoce ningún catálogo: pregunta a `ParametricRegistry` la definición y actúa en consecuencia. | Evita copiar/pegar el mismo index/store/update cinco veces, y evita también seis controladores que serían el mismo. |
| `DashboardController`   | Números del panel, con agregados de la base y no colecciones en PHP.                                                                                      |                                                                                                                     |
| `ApiDocsController`     | Sirve Swagger UI y la especificación OpenAPI 3.1 generada en vivo desde `app/OpenApi/`.                                                                   |                                                                                                                     |

#### Capa 2 — El motor (la parte que más se evalúa)

| Clase                          | Responsabilidad única                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LiquidationEngine`            | **El orquestador.** arma el contexto, llama a las calculadoras en orden, suma, comprueba balances. Es el único lugar que sabe _el orden_ de las cosas.                                                                                                                                                                                                                                                                                                                                                                                        |
| `WorkedDaysCalculator`         | Todo lo de fechas: días comerciales, del año, del semestre, años completos y fracción. Cero fórmulas de dinero.                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `SalaryBaseBuilder`            | Devuelve las **dos** bases (prestacional y vacacional) y avisa si no hay derecho a auxilio de transporte.                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `Parameters`                   | Resuelve SMLMV, tasas, divisores y días **por fecha de retiro**, y con eso la celda digitada `B9` del Excel pasa a ser un dato parametrizado y con vigencia. No consulta la base: recibe el puerto `LegalParameterRepository` y la fecha, y pide por clave (`days()`, `rate()`, `money()`). La lectura concreta la hace `EloquentLegalParameterRepository`, y el SMLMV lo trae `EloquentMinimumWageRepository::forDate()`. Si una clave no está configurada, `ParameterNotConfigured` corta el cálculo en vez de asumir un valor por defecto. |
| `IndemnizationStrategyFactory` | Elige la estrategia de indemnización correcta. Aquí se elimina la comparación de texto `"Termino Fijo"`.                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `LiquidationDraftMapper`       | `app/Application`: el único sitio que traduce un `Contract` de Eloquent a `LiquidationDraft`. Pasa las fechas como cadena, los tres salarios como `Money` y resuelve la regla de indemnización (motivo de retiro → tipo de contrato → `Art64SegunSalario`). El motor nunca ve un modelo.                                                                                                                                                                                                                                                      |

#### Capa 2b — Estrategias: una clase por renglón del Excel

Estas son las 9 clases escritas que implementan `ConceptCalculator`. Cada una hace **una** fórmula y
devuelve **un** renglón; `SocialSecurityContributionCalculator` cubre salud y pensión con un solo
código porque comparten base y porcentaje, y `ManualValueCalculator` atiende los renglones cuyo valor
digita el usuario.

| Clase                                  | Fórmula                             | Divisor |
| -------------------------------------- | ----------------------------------- | ------- |
| `CesantiasCalculator`                  | `BP × días_año ÷ 360`               | 360     |
| `InteresesCesantiasCalculator`         | `cesantías × días × 12 % ÷ 360`     | 360     |
| `PrimaCalculator`                      | `BP × días_año ÷ 360`               | 360     |
| `VacacionesCalculator`                 | `BV × días_vac ÷ 720`               | 720     |
| `SalarioPendienteCalculator`           | `SBM ÷ 30 × días_mes`               | 30      |
| `AuxilioTransporteCalculator`          | `AT ÷ 30 × días_mes`                | 30      |
| `IndemnizacionCalculator`              | `SBM × días_indemnizables ÷ 30`     | 30      |
| `SocialSecurityContributionCalculator` | `(salario_pend + vacaciones) × 4 %` | —       |
| `ManualValueCalculator`                | monto digitado                      | —       |

**¿Por qué una clase por fórmula en vez de un `switch`?** Dos razones que vale exponer en la sustentación:

1. Si el cliente cambia la fórmula de vacaciones, se toca **un archivo** y queda claro que no se rompieron las cesantías.
2. Cada calculadora se prueba sola: `assertSame(1075000, $cesantias->compute($ctx)->amount)`. Un `switch` de 200 líneas no se prueba así.

Y las cinco clases de indemnización existen porque el Art. 64 tiene **regímenes distintos**, no una
fórmula con muchos `if`: `SinIndemnizacion`, `TerminoFijoPlazoRestante` y las dos proporcionales
`IndefinidoMenos10Smlmv` (30 + 20) e `IndefinidoMas10Smlmv` (20 + 15), que heredan de la abstracta
`ProportionalByYears`. Esa abstracta no es un adorno de herencia: en ella vive el cálculo del primer
año, los años siguientes y la fracción proporcional, y las dos hijas solo aportan la regla y las dos
claves de `legal_parameters` de las que se leen los días. Cambiar 30 por 32 no exige tocar PHP.

#### Capa 3 — Value objects y DTOs

| Clase                               | Qué es                                                                                                                                                               | Ejemplo                                                               |
| ----------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| `Money`                             | Un objeto que representa plata y sabe operar con ella sin coma flotante.                                                                                             | `Money::of(2150000)->times(180)->dividedBy(360)->rounded()->amount()` |
| `WageSet`                           | El paquete (salario básico, auxilio, promedio de horas extras) que entra al motor.                                                                                   |                                                                       |
| `SalaryBases`                       | El paquete (base prestacional, base vacacional) que sale.                                                                                                            |                                                                       |
| `SalaryBaseAssembly`                | El intermedio que `SalaryBaseBuilder` produce cuando necesita devolver las bases y los avisos juntos.                                                                |                                                                       |
| `DayCounts`                         | (laborados, del año, del semestre, del mes, cesantías, prima, vacaciones).                                                                                           |                                                                       |
| `Tenure`                            | (años completos, días de fracción, ¿menos de un año?).                                                                                                               |                                                                       |
| `Parameters`                        | Los parámetros legales ya resueltos, leídos por clave desde `legal_parameters`. Da igual si se consulta por fecha o por año: ningún número de ley vive en el código. | `$parameters->money('cesantia_interest_rate')`                        |
| `ConceptDefinition` y `ConceptList` | Un concepto del catálogo y la lista cerrada de los que entran al cálculo.                                                                                            |                                                                       |
| `LiquidationReceipt`                | El comprobante con el folio, las fechas y el valor.                                                                                                                  |                                                                       |
| `LiquidationContext`                | **La "mochila"** que viaja por todas las calculadoras con los datos ya resueltos.                                                                                    |                                                                       |
| `LiquidationDraft`                  | Lo que el usuario digita en el formulario.                                                                                                                           |                                                                       |
| `Computation`                       | Lo que devuelve una calculadora: base, cantidad, divisor, fórmula en texto y monto.                                                                                  |                                                                       |
| `ComputedLines`                     | El acumulado de renglones ya calculados, para que unas calculadoras lean a las anteriores (los intereses leen a las cesantías).                                      |                                                                       |
| `IndemnizableDays`                  | El desglose de días de la indemnización: primer año, años siguientes y fracción, con su texto.                                                                       |                                                                       |
| `LiquidationLine`                   | Un renglón con su fórmula en texto.                                                                                                                                  |                                                                       |
| `LiquidationResult`                 | Renglones + A + B + Neto + avisos.                                                                                                                                   |                                                                       |

Los nombres `Percentage`, `SalarySet`, `WorkedDays` y `LegalSnapshot` eran del diseño inicial y no
existen: el porcentaje se resuelve con `Money` y claves de parámetro, y lo demás quedó en `WageSet`,
`DayCounts` y `Parameters`.

> **DTO** = Data Transfer Object = "clase que solo traslada datos, sin lógica".
> **¿Para qué sirven?** Para que una función reciba _un_ objeto claro en vez de 7 parámetros sueltos.
> Comparar: `calcular($salario, $aux, $extras, $fechaInicio, $fechaFin, $tasa, $dias)` (imposible de leer,
> fácil de confundir el orden) contra `calcular($context)` (claro).

#### Capa 4 — Modelos Eloquent

En el diagrama van todas las tablas previstas convertidas en clases. En `app/Models` ya están las diecisiete,
una por tabla: `ActivityLog`, `Company`, `ConceptCatalog`, `Contract`, `ContractType`, `DocumentType`,
`LegalParameter`, `Liquidation`, `LiquidationFile`, `LiquidationItem`, `MinimumWage`, `OvertimeRecord`,
`Position`, `TerminationReason`, `User`, `UserCompany` y `Worker`. La estructura de tablas está explicada en el
Documento 3 (base de datos). Dos detalles:

- El paso de la fila de `contracts` al borrador del motor no lo hace el modelo: lo hace
  `LiquidationDraftMapper` (`app/Application`), que recibe el contrato, la fecha de retiro, la variante
  y los días digitados y arma el `LiquidationDraft`. El modelo queda en cast, relaciones y etiquetas de
  texto, y el motor nunca recibe un `Contract` "crudo" con `$contract->base_salary_monthly` como string.
  El mismo mapeo decide la variante sugerida y la regla de indemnización que le corresponde.
- En todos los modelos, las columnas monetarias se castean a `decimal` (`decimal:2`, `decimal:4`, `decimal:5`
  según la columna) y las fechas a `date` en `casts()`. Ningún `numeric` de Postgres llega a `float`.

#### Capa 5 — Salida y auditoría

| Clase                           | Qué hace                                                                                                                                                                                                                                                                                                                              |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `EloquentLiquidationRepository` | Traduce `LiquidationResult` → filas `liquidations` + `liquidation_items` en una transacción, congela en `params_snapshot` los parámetros con los que se calculó y numera el folio: `nextFolio()` busca el último `LIQ-AAAA-%` con `lockForUpdate()` y le suma uno, así que el contador vive en la base y no en una clase de servicio. |
| `LiquidationWorkbookExporter`   | Arma el `.xlsx` de dos hojas: la liquidación con las 5 columnas del cliente y la hoja de instrucciones y normativa.                                                                                                                                                                                                                   |
| `LiquidationGuidePresenter`     | Fuente única del texto de la guía: pasos, reglas del art. 64 y fórmulas. Lo consumen el exportador y las pantallas.                                                                                                                                                                                                                   |
| `ActivityLogger`                | Escribe la bitácora de auditoría en `activity_logs`.                                                                                                                                                                                                                                                                                  |

### C.3 Qué pasa cuando se pulsa el botón "Liquidar" (recorrido completo)

Este es el texto que hay que poder recitar en la sustentación.

```
1  NAVEGADOR    → mientras se digita: POST /contratos/7/liquidar/vista-previa con la cabecera
                  X-XSRF-TOKEN leída de la cookie XSRF-TOKEN (debounce de 350 ms y AbortController
                  para cancelar la petición que quedó vieja)
2  ROUTA        → routes/web.php encuentra LiquidationController@preview
3  MIDDLEWARE   → auth + EnsureUserIsAdmin donde corresponde → deja pasar o devuelve 403
4  FORMREQUEST  → StoreLiquidationRequest::rules() valida (días enteros en rango, variant ∈ enum);
                  fuera de rango responde 422 con los errores en JSON y la pantalla avisa
5  CONTROLADOR  → LiquidationDraftMapper::fromContract(contrato, fecha de retiro, variante, días,
                  valores manuales) → LiquidationDraft
                  ── a partir de aquí NO hay nada de HTTP ──
6  ENGINE       → Parameters (parámetros legales de la vigencia de la fecha de retiro) y
                  MinimumWageRepository::forDate() → SMLMV y auxilio del año correcto
7  ENGINE       → SalaryBaseBuilder::of(borrador, salarios, parámetros)
                  → bases prestacional y vacacional, y los avisos si no hay derecho a auxilio
8  ENGINE       → WorkedDaysCalculator: días comerciales del año, del semestre, días naturales de la
                  antigüedad y, si hay vencimiento pactado, días del plazo restante
9  ENGINE       → DayCounts y Tenure::fromTotalDays() → 3 años + 109 días
10 ENGINE       → IndemnizationStrategyFactory::make(regla, modalidad, salario básico, SMLMV,
                  parámetros)                      → IndefinidoMenos10Smlmv
11 ENGINE       → arma LiquidationContext (la mochila)
12 ENGINE       → recorre el catálogo activo en orden de secuencia y llama a su calculadora:
                    CesantiasCalculator          → 1.075.000   fórmula "(Base × Días) / 360"
                    InteresesCesantiasCalculator →    64.500   ← lee el renglón anterior
                    PrimaCalculator              → 1.075.000
                    VacacionesCalculator         →    39.602
                    SalarioPendienteCalculator   →   875.453
                    AuxilioTransporteCalculator  →   124.548
                    IndemnizacionCalculator      → 4.434.027
                    SocialSecurityContributionCalculator → salud 36.602 · pensión 36.602
13 ENGINE       → LiquidationResult::assemble() sumadores y deducciones:
                  A = 7.688.130 · B = 73.204 · Neto = 7.614.926, y comprueba A − B == Neto
14 ENGINE       → devuelve LiquidationResult
                  ── vuelve el mundo HTTP ──
15 VISTA PREVIA → el controlador responde JSON con lines, totals, notices e indemnization_rule.
                  No se escribe una sola fila.
16 CONTROLADOR → al pulsar "Guardar" es el mismo camino en store(); el resultado se persiste con
                  EloquentLiquidationRepository en una transacción, se congela params_snapshot, se
                  genera el folio y ActivityLogger deja la huella
17 CONTROLADOR → Inertia::render('liquidations/show') muestra el documento guardado
18 CONTROLADOR → si pulsa "Excel": LiquidationWorkbookExporter::export() escribe el temporal en
                  storage/app/private/exports, agrega la hoja de instrucciones con
                  LiquidationGuidePresenter y entrega una descarga que borra el archivo al terminar
```

### C.4 Así quedó una calculadora (código real de `app/Domain/Calculation/Calculators`)

```php
<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class CesantiasCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'cesantias';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->cesantias > 0;
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->prestacional;
        $days = $context->days->cesantias;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('cesantias_divisor');

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Base Prestacional × Días) / %d', $divisor),
            $base->times($days)->dividedBy($divisor)->rounded(),
        );
    }
}
```

Cuatro cosas a notar en este archivo:

1. **No hay `$request` ni `DB::`** → se puede probar sin servidor ni base de datos.
2. **`formulaText` viaja con el renglón** → la columna "Fórmula Aplicada" del Excel sale gratis, y es
   el mismo texto que se guarda congelado en `liquidation_items`.
3. **El orden de las operaciones no es negotiable**: primero `times($days)`, después `dividedBy()`. Al
   revés, el auxilio de transporte da 124.547 en vez de 124.548 y el neto queda un peso abajo (RN-08b
   y el Documento 1, §3.4).
4. **El divisor viene del catálogo o de `legal_parameters`**, no de un literal. Por eso `computes()`
   decide con los días y no con un `if` del concepto: una fila desactivada en `concept_catalogs` saca
   el renglón del cálculo sin tocar PHP.

La condición de `appliesSocialSecurity` no vive en la calculadora sino en `ConceptDefinition`, leída de
la columna `applies_ss_contribution` del catálogo: así `SocialSecurityContributionCalculator` sabe
sobre qué base aplicar el 4 % sin hardcodear ninguna lista de conceptos.

### C.5 Errores de diseño que este diagrama ayuda a evitar

| Error típico de junior                                         | Aquí se evita porque                                                                                                                                                     |
| -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Poner las fórmulas en el controlador                           | El motor es independiente de HTTP (§C.3 paso 6).                                                                                                                         |
| Un `LiquidationService` de 400 líneas con `if` por todos lados | Una clase por concepto (§C.2 capa 2b).                                                                                                                                   |
| Calcular con `float` y que el neto salga `$7.614.925,99`       | `Money` con enteros + redondeo por línea (RN-08).                                                                                                                        |
| Comparar `"Término Fijo" == $contract->type->name`             | Enum `ContractModality` + Factory (§C.2 capa 2).                                                                                                                         |
| Guardar solo el monto final en la BD                           | `liquidation_items` con base, días, divisor y fórmula.                                                                                                                   |
| Recalcular la liquidación cuando cambia el SMLMV               | `params_snapshot` congelado (RN-11).                                                                                                                                     |
| Reescribir el CRUD 5 veces para los catálogos                  | `ParametricController` genérico + `ParametricRegistry`, que declara campos, reglas y dependencias de cada catálogo. Agregar un catálogo es una entrada más del registro. |
| Query N+1 en el listado de contratos                           | Eloquent relationships + `with()` en el controlador.                                                                                                                     |

### C.6 Qué NO conviene hacer (contra-el-diseño)

Para una prueba técnica de 4 días, **no** conviene agregar: inyección de contenedores manual, CQRS, eventos,
`queue` para el Excel, repositorios con interfaz + implementación para cada modelo, microservicios,
Docker multi-stage, ni un paquete propio de dominio. Todo eso se ve bien pero consume el tiempo que
hace falta para que las fórmulas salgan exactas.

**Versión honesta de la arquitectura:** carpetas ordenadas en `app/Domain/Calculation`, controladores
delgados, `FormRequest` para validar, y `Policies` para roles. Nada más.
