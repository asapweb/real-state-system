# Gestión de Ajustes de Contrato

## Objetivo

Permitir registrar, visualizar y controlar los ajustes previstos en los contratos de alquiler, ya sea por porcentaje, valor fijo, índices u otro criterio negociado. Estos ajustes pueden o no tener un valor cargado desde el inicio, y el sistema debe asistir al usuario en su seguimiento y aplicación.

## Modelo involucrado

**contract_adjustments**  
Cada registro representa un ajuste previsto dentro del contrato, con la siguiente información:

- `contract_id`: Contrato asociado.
- `type`: Tipo de ajuste (`fixed`, `percentage`, `index`, `negotiated`).
- `value`: Valor aplicado (opcional al inicio).
- `notes`: Descripción libre o nombre del índice.
- `index_type_id`: En caso de que se use índice, se asocia a un tipo.
- `effective_date`: Fecha en que el ajuste entra en vigencia.
- `applied_at`: Fecha en que el ajuste fue efectivamente aplicado (se setea automáticamente al generarse la cobranza).
- `created_at`, `updated_at`.

## Estados lógicos posibles

Se derivan dinámicamente según la fecha y valor:

- `pending`: El ajuste aún no venció y no tiene valor.
- `expired_without_value`: La fecha de vigencia ya pasó y sigue sin valor.
- `with_value`: Tiene valor y fecha válida, pero aún no se usó.
- `applied`: Ya se utilizó en una cobranza mensual.

## Reglas de carga

- Se cargan desde la vista del contrato.
- Se pueden dejar sin valor (por ejemplo, un ajuste por índice aún no publicado).
- El valor y nota pueden ser completados posteriormente desde el módulo de gestión.
- Si se modifica el tipo o fecha, debe editarse desde el contrato.

## Interfaz global (Gestión de Ajustes)

- Vista: `ContractAdjustmentIndex.vue`
- Tabla: `ContractAdjustmentTable.vue`
- Se puede filtrar por:
  - Estado (`status`)
  - Tipo de ajuste
  - Rango de fechas (`effective_date_from`, `effective_date_to`)
- Solo permite editar el valor (`value`) y la nota (`notes`) a través de un diálogo (`ContractAdjustmentValueDialog.vue`).
- No permite eliminar ni modificar el tipo de ajuste ni la fecha.

## Interfaz desde el contrato

- En la vista `ContractShow.vue`, los ajustes se listan en una tarjeta adicional debajo de la información principal.
- Se muestran todos los ajustes del contrato, permitiendo:
  - Edición completa del ajuste si aún no fue aplicado.
  - Visualización de si ya fue usado.
  - Link a la cobranza donde fue aplicado.

## Integración con cobranzas

- Al generar la cobranza (`CollectionGenerationService`), se busca el ajuste más reciente cuya `effective_date <= fin de mes`.
- Si tiene `value` definido, se aplica automáticamente.
- Si el contrato tiene prorrateo y el ajuste está vigente, se aplica proporcionalmente.
- Una vez utilizado, se setea `applied_at`.
