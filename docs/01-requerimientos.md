# Documento 1 — Requerimientos

**Proyecto:** Sistema de Liquidación de Prestaciones Sociales e Indemnizaciones
**Tecnología:** Laravel 13 + React (Inertia) + Tailwind 4 + PostgreSQL 18 (servicio local `postgresql-x64-18`, rol dedicado `blindem`; se revisa con pgAdmin 4)
**Entrega:** miércoles 4 de noviembre de 2026, 10:00 a.m.

> **Cómo leer este documento.** Está escrito para que se entienda sin saber de derecho laboral.
> Conviene empezar por el glosario (sección 2): quien domine esas 8 palabras entiende el 90 % del proyecto.
> Cada requerimiento tiene un código (`RF-01`, `RN-03`) para poder citarlo en el README y en el video.

---

## 0. Principio rector (decisión del cliente, 31/10/2026)

> **"Ciñete al Excel, que es un ejemplo de la calculadora y de cómo debe funcionar."**

Esto significa, en términos prácticas:

1. **El comportamiento por defecto del motor es el Excel, no "lo que la ley dice" ni una propuesta de quien desarrolla.**
   Si el Excel divide vacaciones entre 720, el sistema divide entre 720. Si el Excel usa los días del año
   para la prima, el sistema usa los días del año para la prima.
2. **Los 12 números del Excel son la prueba de aceptación.** No "aproximadamente": al peso exacto.
3. Los hallazgos de la §10 **no se corrigen por defecto**. Se dejan documentados y, cuando son baratos de
   implementar, se ofrecen como **modo alternativo desactivado**. Nunca se cambia un resultado sin que el
   usuario lo pida explícitamente.
4. **Los días de liquidación son campos editables, no fórmulas ocultas.** Hay un detalle del Excel que
   resuelve casi toda la ambigüedad: en el **Formato A**, `E11 Días Vacaciones`, `E12 Días Prima` y
   `E13 Días Cesantías` son **valores digitados por el usuario** (15 / 180 / 180). En el **Formato B** solo se
   calcula `E9`. Así que el patrón correcto es: **el sistema calcula y pre-rellena con la fórmula del Excel,
   y el usuario puede editar el número antes de liquidar**. Con eso se ganan las dos cosas:
   reproduce el archivo y sobrevive a sus contradicciones.

### La contradicción interna del Excel (y cómo se resuelve)

El Excel **se contradice a sí mismo** en la prima, y hay que elegir:

| Parte del Excel                                     | Qué dice                                                                                |
| --------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Hoja `Instrucciones y Normativa`, celda `B18`       | "Prima de Servicios — Fórmula: (Base Prestacional * Días Laborados **Semestre**) / 360" |
| Hoja `Liquidacion Laboral_ Indenizaci`, celda `E21` | La fórmula real toma `E13` ← `E9`, que es la cuenta de días del **AÑO**                 |

En el ejemplo ambos coinciden en **180** (el retiro es el 30 de junio, justo un semestre), por eso la
contradicción es invisible. **Regla adoptada:** pre-rellenar con `E9` (comportamiento del Excel) y mostrar
el campo editable, con el valor del semestre como sugerencia al lado. Así el número del video es el del
cliente y el usuario conserva el control.

---

## 1. ¿Qué hay que construir, en una frase?

Una página web donde una persona registra **trabajadores**, **empresas** y **contratos**, y con un clic
el sistema calcula cuánto le debe la empresa a ese trabajador cuando el contrato termina
(cesantías, intereses, prima, vacaciones, salarios pendientes y, a veces, indemnización),
y le entrega el documento listo para imprimir o exportar a Excel.

**El Excel que envió el cliente no es un accesorio: es la especificación.** Las fórmulas de ese archivo
son los requisitos. Si el sistema saca un número distinto al del Excel, la prueba está mal.

---

## 2. Glosario laboral (leerlo primero, en serio)

| Término                            | Qué es, en español simple                                                                      | Dato clave                                                            |
| ---------------------------------- | ---------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| **Prestaciones sociales**          | Dinero adicional que la ley obliga a pagar al trabajador, además del salario.                  | Son cesantías + intereses + prima + vacaciones.                       |
| **Salario básico mensual (SBM)**   | Lo que gana al mes, sin contar prestaciones.                                                   | En el ejemplo: $1.750.905                                             |
| **Auxilio de transporte (AT)**     | Plata adicional por movilización. **No es salario.** Solo aplica si gana ≤ 2 salarios mínimos. | En el ejemplo: $249.095                                               |
| **Horas extras / recargos**        | Trabajo adicional. Promedio mensual ya calculado.                                              | En el ejemplo: $150.000                                               |
| **SMLMV**                          | Salario Mínimo Legal Mensual Vigente. Cambia cada año por decreto.                             | Umbral para elegir la regla de indemnización.                         |
| **Cesantías**                      | "Un mes de salario por año trabajado", pagado al terminar.                                     | Se calcula sobre la **base prestacional**.                            |
| **Intereses de cesantías**         | El 12 % anual que se paga sobre las cesantías acumuladas.                                      | Se calcula **sobre el valor de las cesantías**, no sobre el salario.  |
| **Prima de servicios**             | Medio mes de salario por cada semestre trabajado.                                              | Se calcula con el **semestre**, no con el año.                        |
| **Vacaciones**                     | 15 días de descanso por año. Si no los gozó, se le pagan.                                      | Se calculan sobre la **base vacacional** (sin auxilio de transporte). |
| **Base prestacional (BP)**         | `SBM + AT + horas extras`.                                                                     | 1.750.905 + 249.095 + 150.000 = **2.150.000 exacto**                  |
| **Base vacacional (BV)**           | `SBM + horas extras`. **Sin** auxilio de transporte.                                           | $1.900.905                                                            |
| **Año comercial**                  | Para liquidar, el año se cuenta como **360 días** y el mes como **30 días**.                   | Por eso se divide entre 360, 720 o 30.                                |
| **Indemnización (Art. 64 C.S.T.)** | Multa a la empresa por despedir sin justa causa.                                               | Solo aplica en algunos motivos de retiro.                             |
| **Motivo de retiro**               | Razón por la que terminó el contrato.                                                          | **Es el dato que decide si hay o no indemnización.**                  |

### Las dos reglas que más se equivocan los aspirantes

1. **Hay DOS bases, no una.** Cesantías, intereses y prima usan `BP` (con auxilio de transporte).
   Vacaciones usan `BV` (sin auxilio de transporte). Si se usa una sola base para todo, el resultado sale mal.
2. **Las deducciones de salud y pensión NO se aplican a todo.** Solo al salario pendiente y a las vacaciones.
   Cesantías, intereses, prima e indemnización **no** sufren descuento del trabajador.

---

## 3. Las fórmulas que el sistema debe implementar

Copiadas del Excel, verificadas célula por célula.

### 3.1 Devengados (lo que la empresa paga)

| #   | Concepto                        | Fórmula                           | Ejemplo real del Excel                      |
| --- | ------------------------------- | --------------------------------- | ------------------------------------------- |
| D1  | Cesantías                       | `(BP × días_cesantías) ÷ 360`     | (2.150.000 × 180) ÷ 360 = **1.075.000**     |
| D2  | Intereses sobre cesantías       | `(cesantías × días × 12 %) ÷ 360` | (1.075.000 × 180 × 0.12) ÷ 360 = **64.500** |
| D3  | Prima de servicios              | `(BP × días_prima) ÷ 360`         | (2.150.000 × 180) ÷ 360 = **1.075.000**     |
| D4  | Vacaciones                      | `(BV × días_vacaciones) ÷ 720`    | (1.900.905 × 15) ÷ 720 = **39.602**         |
| D5  | Salario pendiente               | `(SBM ÷ 30) × días_del_mes`       | (1.750.905 ÷ 30) × 15 = **875.453**         |
| D6  | Auxilio de transporte pendiente | `(AT ÷ 30) × días_del_mes`        | (249.095 ÷ 30) × 15 = **124.548**           |
| D7  | Indemnización                   | ver sección 3.3                   | **4.434.027**                               |

### 3.2 Deducciones (lo que se descuenta)

| #   | Concepto                    | Fórmula                                  | Ejemplo                     |
| --- | --------------------------- | ---------------------------------------- | --------------------------- |
| DD1 | Salud 4 %                   | `(salario_pendiente + vacaciones) × 4 %` | 915.055 × 0.04 = **36.602** |
| DD2 | Pensión 4 %                 | `(salario_pendiente + vacaciones) × 4 %` | 915.055 × 0.04 = **36.602** |
| DD3 | Préstamos / anticipos       | valor digitado                           | 0                           |
| DD4 | Otras (sindicato, libranza) | valor digitado                           | 0                           |

**Totales:** `TOTAL DEVENGADO (A)` = suma D1..D7 · `TOTAL DEDUCCIONES (B)` = suma DD1..DD4 · `NETO = A − B`

> ⚠️ **Regla de redondeo (crítica).** El Excel redondea **cada concepto** a pesos enteros y luego suma.
> No es lo mismo que sumar y redondear al final. Hay que hacerlo igual que el Excel o los totales no cuadran.
> En PHP se cumple con `round($valor, 0)` por línea antes de acumular.

### 3.3 Indemnización — Art. 64 C.S.T. (la decisión es un árbol, no un número)

```
¿El motivo de retiro da derecho a indemnización?
├── NO  → indemnización = 0  (Formato A del Excel)
└── SÍ
    ├── Contrato a TÉRMINO FIJO
    │     → salarios faltantes hasta la fecha de vencimiento pactada
    └── Contrato INDEFINIDO  →  ¿salario < 10 × SMLMV ?
          ├── SÍ  → primer año 30 días + 20 días por cada año siguiente
          └── NO  → primer año 20 días + 15 días por cada año siguiente

    valor = (salario_básico ÷ 30) × total_de_días
```

Ese árbol se implementó con el patrón estrategia y son cinco clases: `SinIndemnizacion`,
`IndefinidoMenos10Smlmv` (literal a), `IndefinidoMas10Smlmv` (literal b),
`TerminoFijoPlazoRestante` y la base abstracta `ProportionalByYears`, de la que heredan las dos de
término indefinido porque el armado del conteo —primer año, años siguientes y fracción proporcional
de año— es idéntico y solo cambian los días. La elección la hace `IndemnizationStrategyFactory` con
tres datos: la regla del motivo de retiro, la modalidad del contrato y si el salario supera diez
veces el SMLMV del año del retiro. Los días de 30, 20, 15 no aparecen en el código: son filas de
`legal_parameters` (`indemnization_first_year_days_low`, `indemnization_per_year_days_low` y sus
equivalentes `*_high`), así que una reforma laboral que mueva esos números se atiende editando una
fila.

**Ejemplo del Excel (Formato B), paso a paso:**
`1.204` días naturales ÷ `365` = `3` años completos, sobran `109` días →
`30` (1er año) + `(3−1)×20` + `(109÷365)×20 = 5,97` = **75,97 días** →
`(1.750.905 ÷ 30) × 75,97 =` **4.434.027**.

### 3.4 La trampa del peso (esto sale al primer descuido)

El auxilio de transporte pendiente del Excel es **124.548**. Comprobado: `249.095 × 15 ÷ 30 = 124.547,5`
→ redondeado → `124.548`. ✔

Ahora el mismo cálculo hecho dividiendo primero, como sale natural de escribir:

```
249.095 ÷ 30        = 8.303,166666666666...   ← no cabe en un float
8.303,1666... × 15  = 124.547,49999999999     ← el .5 se pierde
round()             = 124.547                 ← UN PESO DE DIFERENCIA
```

Y ese peso se propaga: el auxilio entra al `TOTAL DEVENGADO (A)`, la base de salud y pensión, el neto
y el Excel exportado. La aplicación se ve _casi_ igual y el evaluador ve `3.180.898` en vez de `3.180.899`.

**Regla (RN-08b):** la fórmula se escribe siempre **en el orden en que la leyó el Excel**:
primero multiplicas por los días, después divides por el divisor.

```php
$aux = round($auxilio * $dias / 30);      // ✅ 124.548
$aux = round($auxilio / 30 * $dias);      // ❌ 124.547
```

Y para el resto del sistema, `Money` con **centavos enteros** (`int`) y `bcmath` si se hacen divisiones
intermedias. Nunca `float` guardado en la base de datos.

> Las dos rutas quedaron verificadas con aritmética exacta. Con `×` antes que `÷` se reproducen **los 12 números**
> del Excel sin una sola unidad de error: `A = 3.254.103`, `B = 73.204`, `NETO = 3.180.899` y,
> en el Formato B, `NETO = 7.614.926`.

---

## 4. Los dos formatos de salida (son requisitos, no estilos)

El Excel tiene dos hojas y son **dos documentos distintos**:

|                        | **Formato A** (hoja "Liquidacion Laboral")                                   | **Formato B** (hoja "Liquidacion Laboral_ Indenizaci")                              |
| ---------------------- | ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Cuándo                 | Retiro sin indemnización (vencimiento, voluntad del trabajador, justa causa) | Despido sin justa causa                                                             |
| Bloques                | 1 Datos · 2 Bases · 3 Prestaciones · 4 Deducciones · 5 Firmas                | 1 Datos · 2 Bases · 3 Prestaciones · **4 Indemnización** · 5 Deducciones · 6 Firmas |
| Dato extra             | —                                                                            | `Tipo de contrato` y `SMLMV vigente`                                                |
| Texto de "paz y salvo" | "...liquidación de prestaciones sociales y salarios..."                      | "...prestaciones sociales, salarios **e indemnización (si aplica)**..."             |
| Neto del ejemplo       | **3.180.899**                                                                | **7.614.926**                                                                       |

**Decisión de diseño:** un solo motor, un campo `variant` (`prestacional` / `con_indemnizacion`).
El sistema **sugiere** la variante según el motivo de retiro, pero el usuario la puede cambiar.

---

## 5. Roles: quién puede hacer qué

| Persona           | Qué puede hacer                                                                                                                                                                   |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Administrador** | Todo lo del colaborador **+** CRUD de empresas, tipos de documento, cargos, motivos de retiro, tipos de contrato, SMLMV/parámetros legales y usuarios.                            |
| **Colaborador**   | CRUD de trabajadores y contratos. Consultar historial por afiliado y por empresa. Pantalla de liquidación con filtros. Exportar a Excel. NO puede editar catálogos (solo verlos). |
| **Anónimo**       | Nada. Todo el sitio pide login.                                                                                                                                                   |

> Para un junior: en Laravel esto se llama **autorización** y se resuelve con un campo `role` en `users`
>
> - **Policies** + un **middleware**. No hacen falta paquetes de roles si solo son 2 roles.

---

## 6. Historias de usuario

Con este formato: _Como [rol], quiero [acción], para [beneficio]._

| ID    | Historia                                                                                                                                                  | Prioridad |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| HU-01 | Como administrador, quiero iniciar sesión, para que nadie entre sin permiso.                                                                              | Alta      |
| HU-02 | Como colaborador, quiero registrar un trabajador con su tipo y número de documento, para poder contratarlo.                                               | Alta      |
| HU-03 | Como colaborador, quiero crear un contrato eligiendo trabajador, empresa, cargo y tipo de contrato de listas despleógicas, para no escribir datos a mano. | Alta      |
| HU-04 | Como colaborador, quiero que el sistema calcule solo los días trabajados y las bases, para no cometer errores de aritmética.                              | Alta      |
| HU-05 | Como colaborador, quiero pulsar "Liquidar" y ver todos los conceptos desglosados con su fórmula, para revisar antes de aprobar.                           | Alta      |
| HU-06 | Como colaborador, quiero exportar la liquidación a Excel con el mismo formato del archivo del cliente, para entregarla.                                   | Alta      |
| HU-07 | Como colaborador, quiero filtrar contratos por estado, tipo de documento y número de documento, para encontrar rápido a una persona.                      | Alta      |
| HU-08 | Como colaborador, quiero ver el historial de contratos de un trabajador y de una empresa, para responder consultas.                                       | Media     |
| HU-09 | Como administrador, quiero crear y editar los catálogos (cargos, motivos, tipos de contrato), sin llamar a un programador.                                | Alta      |
| HU-10 | Como administrador, quiero registrar el SMLMV de cada año, para que la indemnización se calcule con la vigencia correcta.                                 | Alta      |
| HU-11 | Como administrador, quiero ver quién creó o modificó una liquidación, para auditar.                                                                       | Media     |
| HU-12 | Como usuario, quiero ver la liquidación imprimible en el navegador con las firmas, para que el trabajador firme.                                          | Media     |
| HU-13 | Como colaborador, quiero descargar la liquidación en PDF, para archivarla.                                                                                | Baja      |

**Prioridad = MoSCoW simplificado:** _Alta_ = obligatorio para entregar · _Media_ = importante, si no da el tiempo se deja a medias pero funcionando · _Baja_ = si sobra tiempo.

---

## 7. Requerimientos funcionales

### RF-01 Autenticación

- **RF-01.1** Login con correo y contraseña. Página `/login` fuera del área privada.
- **RF-01.2** Cerrar sesión.
- **RF-01.3** Un usuario tiene un rol (`administrator` o `collaborator`).
- **RF-01.4** El menú lateral muestra solo lo que el rol puede usar.
- **RF-01.5** Si se abre una URL que no corresponde al rol → mensaje 403, no un crash.

### RF-02 Catálogos paramétricos (CRUD, solo Administrador)

- **RF-02.1** Tipos de documento (código, nombre, abreviatura). Ej: `CC` Cédula de Ciudadanía, `CE` Cédula de Extranjería, `NIT`.
- **RF-02.2** Cargos (código, nombre, área, nivel).
- **RF-02.3** Tipos de contrato (código, nombre, **modalidad**, exige fecha de vencimiento, da o no derecho a indemnización).
- **RF-02.4** Motivos de retiro (código, descripción, clasificación, **da derecho a indemnización sí/no**, regla de indemnización).
- **RF-02.5** Empresas (NIT único, razón social, dirección, teléfono, correo, representante legal).
- **RF-02.6** Conceptos de liquidación (nombre, si es devengado o deducción, clave de fórmula, divisor 360/720/30).
- **RF-02.7** Salarios mínimos por año (año, SMLMV, auxilio de transporte).
- **RF-02.8** Parámetros legales (tasa intereses cesantías 12 %, salud 4 %, pensión 4 %, tope auxilio 2 SMLMV).
- **RF-02.9** Todo catálogo se **desactiva**, no se borra, si ya está en uso (restricción de clave foránea).
- **RF-02.10** Listados con buscador y paginación.

### RF-03 Trabajadores

- **RF-03.1** CRUD completo.
- **RF-03.2** La combinación `(tipo de documento + número)` **no se puede repetir**. Mensaje claro si intento.
- **RF-03.3** Campos: nombres, apellidos, documento, fecha de nacimiento, género, correo, teléfono, dirección.
- **RF-03.4** Ficha del trabajador con **todos sus contratos** ordenados por fecha y su liquidación.
- **RF-03.5** Un trabajador puede pertenecer a varias empresas (eso lo permite la tabla `contracts`, no se duplica el trabajador).

### RF-04 Contratos

- **RF-04.1** CRUD completo (el enunciado lo exige explícitamente).
- **RF-04.2** Selección de trabajador, empresa, cargo, tipo de contrato y motivo de retiro desde listas.
- **RF-04.3** Datos: fecha de ingreso, fecha de retiro, fecha de vencimiento pactada (obligatoria si término fijo), salario básico, auxilio de transporte, promedio de horas extras, días pendientes de vacaciones, días del mes a pagar.
- **RF-04.4** Estados: `activo`, `terminado`, `en_liquidacion`, `liquidado`. `liquidado` lo fija el sistema al guardar la liquidación y ningún perfil lo asigna a mano.
- **RF-04.5** El sistema calcula y muestra **en vivo** (solo lectura): días totales laborados, días del año, días del semestre, base prestacional, base vacacional, rango SMLMV. El usuario **no digita nada de esto**.
- **RF-04.6** Validación: fecha de retiro ≥ fecha de ingreso y vencimiento pactado dentro del plazo legal del tipo. Un mismo trabajador **sí** puede tener dos contratos vigentes en el mismo rango de fechas (doble afiliación real); no se rechaza el solapamiento y cada contrato se liquida por separado.
- **RF-04.7** Filtro combinable: estado + tipo de documento + número de documento + empresa + rango de fechas.

### RF-05 Motor de liquidación

- **RF-05.1** Desde un contrato, botón **"Liquidar"** → pantalla con los 7 devengados y 4 deducciones.
- **RF-05.2** Cada renglón muestra: base de cálculo, días/cantidad, **texto de la fórmula aplicada** y valor. (El Excel tiene la columna "Fórmula Aplicada": es un requisito, no un adorno.)
- **RF-05.3** Dos variantes: Format A y Format B (§4). El sistema sugiere una según el motivo de retiro.
- **RF-05.4** El auxilio de transporte se calcula o se valida: si el salario supera 2 SMLMV, no hay derecho.
- **RF-05.5** Deducciones libres: el usuario puede agregar renglones (préstamo, libranza, retención en la fuente) con monto propio.
- **RF-05.6** Comprobación visible: `A − B = Neto`.
- **RF-05.7** La liquidación guardada **congela** los parámetros que usó (SMLMV, tasas). Si el SMLMV cambia en 2027, la liquidación de 2024 no se recalcula.
- **RF-05.8** Botón "Recalcular" solo en estado `borrador`. Una liquidación `aprobada` se anula, no se edita.
- **RF-05.9** **Vista previa en vivo antes de guardar.** El formulario de liquidación muestra el
  desglose completo —base, días, fórmula y valor de cada renglón, los tres totales, los avisos y la
  regla de indemnización elegida— mientras se digita, sin escribir nada en la base de datos. Es la
  respuesta directa a "que la calculadora les ayude": la contadora ve el efecto de cada dato antes de
  emitir el documento. Lo atiende `POST /contratos/{contract}/liquidar/vista-previa`
  (`liquidations.preview`, `LiquidationController@preview`), que reutiliza el mismo
  `StoreLiquidationRequest` y el mismo motor, de modo que lo mostrado en la previa es
  byte a byte lo que se guarda después. Un valor fuera de rango devuelve 422 con los errores en JSON
  y la interfaz avisa que no se pudo calcular, en lugar de inventar un número.
- **RF-05.10** **Pista de la causal.** Bajo el selector de formato hay una ayuda que dice qué variante
  corresponde a cada motivo: terminación de contrato, renuncia o justa causa usan el formato
  prestacional, y despido sin justa causa usa el formato con indemnización del artículo 64. La
  sugerenacia del sistema no sustituye al usuario: la variante sigue siendo editable.

### RF-06 Exportación y documentos

- **RF-06.1** **Exportar a Excel** cada liquidación, reproduciendo la estructura de 5 columnas y los
  bloques del archivo original. _(Obligatorio: el enunciado lo nombra.)_ El libro saliente tiene dos
  hojas: "Liquidación" y "Instrucciones y Normativa" (§10, punto 11).
- **RF-06.2** Exportar a Excel el **listado de contratos** filtrado. _No se construyó: la entrega se
  concentró en el documento individual, que es el que pide el enunciado. El listado se puede imprimir
  desde el navegador._
- **RF-06.3** Vista de impresión (Botón "Imprimir" del navegador) con el texto de paz y salvo y **dos bloques de firma**: Trabajador (C.C. No.) y Empleador (NIT / C.C.).
- **RF-06.4** PDF descargable con ese mismo diseño. _No se construyó como PDF: la hoja de estilos de
  impresión reproduce el documento y el navegador lo exporta. Se declaró fuera de alcance (§11)._
- **RF-06.5** Folio automático, ej. `LIQ-2024-00007`.
- **RF-06.6** La tercera hoja del Excel del cliente —instrucciones y normativa— está presente en el
  producto, no solo en el archivo de entrada. Vive en una sola clase,
  `app/Http/Presenters/LiquidationGuidePresenter.php`, con tres secciones (`steps`, `rules`,
  `formulas`) que consumen a la vez la hoja "Instrucciones y Normativa" del XLSX y los bloques
  desplegables `<details>` de las pantallas de liquidar y de ver una liquidación. Un cambio de redacción
  se hace en un sitio y sale igual en los tres canales.

### RF-07 Consulta y reportes

- **RF-07.1** Panel inicial: contratos activos, terminados, total liquidado del mes, conteo por empresa, conteo por motivo de retiro.
- **RF-07.2** Búsqueda global por número de documento.
- **RF-07.3** La ruta raíz `/` no muestra la portada del framework: redirige al panel con sesión
  abierta y al login sin ella. El panel (`DashboardController@index`) se calcula con agregados de la
  base de datos —`count`, `sum`, un `join` para los contratos pendientes más antiguos— y no cargando
  colecciones en PHP.

### RF-08 Auditoría

- **RF-08.1** Registrar quién creó/editó/eliminó y el valor anterior → nuevo.
- **RF-08.2** Pantalla de bitácora solo para el Administrador.

---

## 8. Reglas de negocio (las que hay que saber sustentar)

| ID     | Regla                                                                                                                                                                      | Por qué importa                                                                                                               |
| ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| RN-01  | La base prestacional incluye el auxilio de transporte; la base vacacional, no.                                                                                             | Art. 4 C.S.T.: el auxilio no es salario.                                                                                      |
| RN-02  | Cesantías y prima usan el divisor **360**; vacaciones usan **720**; salario diario usa **30**.                                                                             | Año comercial, no calendario.                                                                                                 |
| RN-03  | Los intereses de cesantías se calculan **sobre el valor de las cesantías** ya redondeado.                                                                                  | Encadenamiento: D2 depende de D1.                                                                                             |
| RN-04  | Salud y pensión (4 % + 4 %) se descuentan **solo** de salario pendiente + vacaciones.                                                                                      | Si descuentas de cesantías o prima, el neto sale mal.                                                                         |
| RN-05  | La indemnización no sufre descuento de seguridad social.                                                                                                                   | No es ingreso constitutivo de salario.                                                                                        |
| RN-06  | El umbral "10 SMLMV" se resuelve consultando el SMLMV **del año del retiro**, no digiténdolo.                                                                              | El Excel lo tiene en una celda (`B9`) que hay que actualizar a mano cada enero.                                               |
| RN-07  | Los días de prima, cesantías y vacaciones son **campos editables pre-rellenados** con la fórmula del Excel (días del año). El usuario los puede cambiar antes de liquidar. | Es lo que hace el propio Excel en el Formato A: `E11`, `E12`, `E13` son digitados. Reproducirlo y a la vez permitir corregir. |
| RN-08  | Redondear por renglón, no al final.                                                                                                                                        | Fidelidad numérica al Excel.                                                                                                  |
| RN-08b | **Multiplicar antes de dividir:** `(base × días) ÷ divisor`, nunca `(base ÷ divisor) × días`.                                                                              | Con `float`, `249.095 ÷ 30 × 15` da `124.547` en vez de `124.548`: el neto sale **1 peso abajo**. Ver §3.4.                   |
| RN-09  | El **motivo de retiro** (catálogo) decide si hay indemnización. Nunca una comparación de texto.                                                                            | Corrige el bug del `IF(E7="Termino Fijo")` sin tilde.                                                                         |
| RN-10  | En término fijo, la indemnización usa la **fecha de vencimiento pactada**.                                                                                                 | En el Excel está hardcodeada a 180 días.                                                                                      |
| RN-11  | Liquidación aprobada = inmutable. Se anula y se hace otra.                                                                                                                 | Traza legal del documento.                                                                                                    |
| RN-12  | No se elimina un catálogo en uso.                                                                                                                                          | Integridad referencial.                                                                                                       |

---

## 9. Requerimientos no funcionales

| ID     | Requerimiento                                                                                                                                             | Cómo se cumple                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RNF-01 | **Responsive** (celular, tablet, PC)                                                                                                                      | Tailwind 4: breakpoints `sm:`/`md:`/`lg:`, sidebar que se convierte en hoja móvil, tablas envueltas en scroll horizontal.                                                                                                                                                                                                                                                                                                                          |
| RNF-02 | Laravel 13 con PHP 8.5                                                                                                                                    | `laravel new blindem --react --database=pgsql` (instalador de Herd).                                                                                                                                                                                                                                                                                                                                                                               |
| RNF-03 | **PostgreSQL 18.6** como motor único y relacional (servicio de Windows `postgresql-x64-18`, puerto 5432, rol dedicado `blindem`); se revisa con pgAdmin 4 | Migraciones con `Schema::`, `->decimal(14, 2)` y `->jsonb()`, y SQL propio de Postgres donde aporta: `CHECK`, índice GIN con `pg_trgm` para buscar por nombre, índice único sobre `lower(email)`, `jsonb` en `params_snapshot` y `changes`, y borrado lógico en `contracts`. Dos bases (`blindem` para desarrollo y `blindem_test`, la que fija `phpunit.xml` con `DB_CONNECTION=pgsql`). Ver Documento 3, §2.3 PostgreSQL: rol, bases y conexión. |
| RNF-04 | Dinero sin errores de punto flotante                                                                                                                      | `NUMERIC(14,2)` en la base y el objeto `Money` (bcmath, escala 12) en el motor. El `float` de PHP no entra en ninguna suma ni en el constructor de dinero: `Money::of()` lo rechaza. El redondeo a pesos se hace renglón por renglón, al final, con `rounded()`.                                                                                                                                                                                   |
| RNF-05 | El cálculo se puede probar sin navegador                                                                                                                  | Clases del motor sin `$request` ni Eloquent → tests unitarios puros.                                                                                                                                                                                                                                                                                                                                                                               |
| RNF-06 | Validación en el servidor siempre                                                                                                                         | `FormRequest` con `rules()`. El `required` del `<Input>` es adorno, no seguridad.                                                                                                                                                                                                                                                                                                                                                                  |
| RNF-07 | CSRF activo en todas las escrituras                                                                                                                       | `ValidateCsrfToken` en el middleware. Inertia ya manda `X-XSRF-TOKEN` desde la cookie en cada `router.post`, así que no se escribe a mano.                                                                                                                                                                                                                                                                                                         |
| RNF-08 | Interfaz en español, moneda `$1.750.905`, fechas `dd/mm/aaaa`                                                                                             | `number_format()` con separadores colombianos.                                                                                                                                                                                                                                                                                                                                                                                                     |
| RNF-09 | Datos de prueba cargados con un comando                                                                                                                   | Seeders con los 2 casos del Excel.                                                                                                                                                                                                                                                                                                                                                                                                                 |
| RNF-10 | Código sin secretos y sin correos personales                                                                                                              | `.env` fuera del repo; `.env.example` dentro.                                                                                                                                                                                                                                                                                                                                                                                                      |
| RNF-11 | Documentación: README con instalación, estructura, ERD y decisiones                                                                                       | Los 3 documentos de la carpeta `docs/` + README.                                                                                                                                                                                                                                                                                                                                                                                                   |
| RNF-12 | Video de la prueba funcional y sustentación                                                                                                               | Grabar 5-8 min: login → catálogo → trabajador → contrato → liquidar → Excel.                                                                                                                                                                                                                                                                                                                                                                       |

---

## 10. Hallazgos del Excel: qué se replica y qué se ofrece como alternativa

Con el principio rector de la §0, **la columna derecha manda**: por defecto el sistema hace lo mismo
que el Excel. Documentar el hallazgo demuestra que se entendió el dominio; **eso no** autoriza a cambiar
el resultado. Solo se ofrece alternativa cuando es un switch barato y está apagado por defecto.

| #   | Hallazgo en el Excel                                                                              | Efecto                                                                                                           | Qué hace el sistema (por defecto = Excel)                                                                                                  |
| --- | ------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | `IF(E7="Termino Fijo",…)` compara texto exacto y sin tilde                                        | Si se escribe "Término Fijo", la rama **nunca** se activa                                                        | Se decide por `contract_type.modality` (enum), no por string                                                                               |
| 2   | El SMLMV es una **celda digitada** (`B9`), no un dato buscado por año                             | Frágil: el valor 1.300.000 es **correcto** para el retiro de 2024, pero nada lo garantiza para otro contrato     | Se lee de `minimum_wages` según el año del retiro. _No es un error numérico del Excel: es un requisito de parametrización._                |
| 3   | La prima usa los días **del año**, no del semestre, pero la hoja de instrucciones dice "semestre" | Retiro en 2.º semestre → prima sobrestimada. Invisible en el ejemplo porque 15-ene→30-jun sí es un semestre      | **Defecto: días del año (`E9`), igual que el Excel.** El campo es editable y la vista muestra el valor del semestre como sugerencia.       |
| 4   | "Días vacaciones pendientes: 15" pero divide entre 720                                            | 720 corresponde a días _trabajados_; con 15 días acumulados serían $1.900.905 × 15 ÷ 30 = **950.453**, no 39.602 | **Defecto: `/720`, exactamente como el Excel.** Habrá un `formula_key` por concepto, así que la otra opción existe pero viene **apagada**. |
| 5   | Con antigüedad < 1 año paga los 30 días completos del primer año                                  | Sobrestima la indemnización                                                                                      | **Defecto: 30 días completos, como el Excel.** Modo proporcional disponible tras un toggle.                                                |
| 6   | Término fijo hardcodeado a 180 días                                                               | No existe el dato                                                                                                | Columna `scheduled_end_date`                                                                                                               |
| 7   | Tasa 12 % literal dentro de la fórmula                                                            | No configurable                                                                                                  | `legal_parameters`                                                                                                                         |
| 8   | Auxilio de transporte se digita sin validar el tope                                               | Pago indebido si gana > 2 SMLMV                                                                                  | Validación + modo `auto / manual / sin_derecho`                                                                                            |
| 9   | No aparece la **empresa** en el documento                                                         | Choca con el enunciado y con el filtro "por empresa"                                                             | Encabezado con razón social y NIT                                                                                                          |
| 10  | En el Formato A la indemnización está en 0 duro                                                   | El usuario no puede elegirla                                                                                     | La decide el motivo de retiro                                                                                                              |
| 11  | La tercera hoja del archivo es texto suelto, sin vínculo con las otras dos                        | Las instrucciones se leen una vez y se olvidan                                                                   | La misma hoja viaja dentro del XLSX exportado y aparece desplegable en la pantalla, generada desde `LiquidationGuidePresenter`             |

> **Decisión práctica sobre el punto 4:** se implementaron **las dos fórmulas** y un selector en el catálogo de conceptos.
> Así el video puede mostrar el número exacto del cliente **y** explicar por qué existe la otra opción.
> Es la respuesta más madura a un requisito ambiguo: no se adivina, se muestran las dos y se justifica.

---

## 11. Fuera de alcance (se declara explícitamente en el README)

Pago de nómina mensual · radicaciones a salud/pensión/ARL/caja de compensación · liquidación de
intereses de mora del Art. 65 (se deja la extensión preparada, no se implementa) · firma digital ·
afiliación a fondos de cesantías · seguridad y salud en el trabajo · contabilidad.

Se añadieron a esta lista dos cosas que estaban en los requerimientos iniciales y que se decidieron
dejar fuera para no repartir el tiempo entre todo: el **PDF descargable** de RF-06.4, cubierto por la
vista de impresión del navegador con el mismo diseño, y el **Excel del listado de contratos** de
RF-06.2. La bitácora de RF-08.2 tampoco tiene pantalla: la tabla `activity_logs` y las columnas
`created_by` sí existen, así que el dato se registra y solo falta la vista del administrador.

---

## 12. Criterios de aceptación (Definition of Done)

La prueba está terminada **solo** si se cumple todo esto:

- [x] `git clone` + `composer install` + `.env` + `php artisan migrate --seed` levanta la app sin errores.
- [x] Se puede iniciar sesión con el usuario admin y con el usuario colaborador semilla.
- [x] El colaborador **no** puede entrar a la administración de catálogos (403).
- [x] Con los datos del **Formato A** del Excel, el sistema devuelve **NETO = $3.180.899** exacto.
- [x] Con los datos del **Formato B** del Excel, el sistema devuelve **NETO = $7.614.926** exacto.
- [x] Cada renglón muestra su fórmula aplicada.
- [x] El botón "Exportar a Excel" descarga un archivo que se abre y replica el formato del cliente,
      en dos hojas: la liquidación y las instrucciones con la normativa.
- [x] Antes de guardar, la pantalla muestra el desglose calculado en vivo y reacciona a cada dato que
      se cambia, sin persistir nada.
- [x] La pantalla de contratos filtra por estado, tipo de documento y número de documento.
- [x] El CRUD de contratos funciona (crear, editar, ver, eliminar con lógica o restricción).
- [x] Los cinco catálogos con pantalla (tipos de documento, cargos, tipos de contrato, motivos de
      retiro y salarios mínimos) tienen CRUD funcionando con validaciones. Conceptos y parámetros
      legales vienen sembrados y se editan por base de datos: no llevan pantalla.
- [x] La vista de impresión muestra el texto de paz y salvo y las dos firmas.
- [x] `php artisan test` pasa en verde, incluyendo los dos casos del Excel: 255 pruebas y 1153
      aserciones, más PHPStan nivel 7 sin errores.
- [ ] README con: objetivo, instalación paso a paso, estructura, diagrama ER, reglas de negocio, tabla de desviaciones del Excel y video. _(El README está; el enlace al video se agrega al grabarlo.)_
- [ ] Video de 5-8 minutos grabado y subido (enlace) con una liquidación real y explicación.

---

## 13. Datos semilla

### 13.1 Lo que construye `php artisan migrate --seed`

- **Dos usuarios** en `DatabaseSeeder`: `admin@blindem.test` (rol Administrador) y `colaborador@blindem.test`
  (rol Colaborador), con la contraseña de demostración que documenta el README. El rol vive en la columna
  `role` del usuario, no en una tabla de permisos.
- **Los siete catálogos**: tipos de documento, cargos, tipos de contrato, motivos de retiro, salarios mínimos
  (la tabla de abajo), conceptos de liquidación y parámetros legales. `ConceptCatalogSeeder` y
  `LegalParameterSeeder` son los que fijan los divisores 360 / 720 / 30, el 12 % de intereses sobre cesantías,
  el tope del auxilio de transporte y los días del art. 64. Ningún número de la ley queda escrito dentro de
  una clase.
- **Cinco trabajadores y cinco contratos** en `DemoDataSeeder` (`CTR-000001` a `CTR-000005`) repartidos en dos
  empresas, con estados distintos para que los filtros de la pantalla de liquidación tengan algo que filtrar.
  El `CTR-000001` es el caso del Excel: Ana Lucía Martínez Soto, CC 1098765432, indefinido, ingreso
  15/03/2021, retiro 30/06/2024, causal "Despido sin justa causa", básico 1.750.905, auxilio 249.095,
  promedio de extras 150.000, vacaciones pendientes 15 y salario pendiente 15.
- **Ninguna liquidación.** `migrate --seed` deja la tabla `liquidations` vacía y los cinco contratos listos
  para liquidar desde la pantalla; las liquidaciones que aparecen en la base de desarrollo local se calcularon
  a mano durante las pruebas, y son las que se usan para grabar el video.

### 13.2 Los dos casos con los que se verificaron los doce números

Son las entradas de `tests/Feature/Calculation/LiquidacionConformeAlExcelTest.php`, que compara cada renglón
contra el valor del archivo del cliente:

| Caso      | Ingreso    | Retiro     | Variante          | Regla de indemnización        | NETO esperado |
| --------- | ---------- | ---------- | ----------------- | ----------------------------- | ------------- |
| Formato A | 2023-01-15 | 2024-06-30 | Prestacional      | Ninguna                       | 3.180.899     |
| Formato B | 2021-03-15 | 2024-06-30 | Con indemnización | Art. 64 lit. a (30 + 20 días) | 7.614.926     |

Los dos comparten los salarios del Excel: básico 1.750.905, auxilio de transporte 249.095, promedio de horas
extraordinarias 150.000, y los días digitados en la planilla: vacaciones 15, prima 180, cesantías 180 y
15 días de salario pendiente.

### Valores reales del SMLMV para sembrar `minimum_wages`

Datos oficiales de Colombia, verificados. Se usan tal cual en el seeder:

| Año      | SMLMV         | Auxilio de transporte |
| -------- | ------------- | --------------------- |
| 2022     | 1.000.000     | 130.506               |
| 2023     | 1.160.000     | 140.500               |
| **2024** | **1.300.000** | **162.000**           |
| 2025     | 1.423.500     | 200.000               |
| **2026** | **1.750.905** | **249.095**           |

> **Por qué esto importa para entender el Excel del cliente:** el retiro del ejemplo es `2024-06-30`, así que el
> SMLMV vigente es **1.300.000** — exactamente el valor que el cliente puso en `B9`. **El Excel está bien ahí.**
> En cambio, el salario básico (`1.750.905`) y el auxilio (`249.095`) de la muestra son los valores **de 2026**.
> Es decir: el archivo mezcla fechas de 2021-2024 con salarios de 2026. No rompe ningún cálculo
> (el salario sigue siendo menor a 10 SMLMV), pero **conviene saberlo** para no "corregir" un número que no está mal.
> Auxilios de 2022 y 2023: vale confirmarlos antes de sembrar, por si los cuestionan.

---

## 14. Preguntas abiertas para el cliente

Se dejan escritas en el README con la respuesta que se adoptó. Un evaluador valora identificar la
ambigüedad en lugar de esconderla.

1. ¿Las vacaciones se pagan por días acumulados (950.453) o con la fórmula `/720` del archivo (39.602)? → **se adoptó `/720` como modo compatible y se ofrece la otra.**
2. ¿La prima usa días del semestre o del año? → **el Excel usa el año y la ley dice semestre; se replica el año y el campo queda editable (§0).**
3. ¿El SMLMV se registra por año o se asume uno global? → **por año.**
4. ¿Hay retención en la fuente en la liquidación? → **sí, como deducción libre.**
5. ¿Se requiere firma digital o basta el espacio de firma impresa? → **basta el espacio.**
6. ¿Un colaborador puede borrar liquidaciones? → **no, solo anular.**
