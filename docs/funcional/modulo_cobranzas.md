# Módulo de Cobranzas – Documentación Funcional

## 🎯 Objetivo
El módulo de cobranzas permite generar comprobantes de deuda mensuales y manuales asociados a contratos de alquiler, asegurando trazabilidad, estructura contable coherente y gestión automatizada de conceptos recurrentes y extraordinarios.

---

## 🧱 Entidades Relacionadas
- `Contract`: contrato de alquiler.
- `Collection`: cobranza (comprobante de deuda).
- `CollectionItem`: ítems incluidos en una cobranza.
- `ContractAdjustment`: ajustes por índice, porcentaje, valor fijo o negociado.
- `ContractExpense`: gastos pagados por la agencia a recuperar (ej. expensas, impuestos).
- `ContractClient`: relación con clientes (inquilinos, garantes, propietarios).

---

## ⚙️ Tipos de Cobranzas

### 1. Mensuales (Automáticas)
- **Disparador**: botón de generación mensual o endpoint `POST /collections/generate`.
- **Condiciones**:
  - Contrato activo para el mes indicado.
  - No debe existir ya una cobranza generada para ese contrato y mes.
  - Deben estar generadas todas las cobranzas de meses anteriores (continuidad).
- **Conceptos incluidos**:
  - Alquiler mensual (con ajustes y prorrateo).
  - Comisión inmobiliaria (si aplica).
  - Seguro del hogar (si está habilitado).
  - Punitorio por mora del mes anterior.
  - Gastos pagados por la agencia no cobrados aún (`ContractExpense`).

### 2. Manual
- Cobranzas ingresadas por el usuario vía formulario.
- Pueden estar asociadas a un contrato, cliente, o ser completamente libres.
- Incluyen ítems con tipo, descripción, monto y moneda.
- Permiten registrar cargos extraordinarios.

---

## 🧮 Cálculos y Reglas

### Ajustes
- Se aplican automáticamente si existe un `ContractAdjustment` con valor definido y fecha efectiva hasta el mes actual.
- Se marca como aplicado al generarse la cobranza.

### Prorrateo
- Se calcula si el contrato empieza o termina en el mes correspondiente y está configurado con `prorate_first_month` o `prorate_last_month`.

### Punitorios
- Se calculan si la cobranza del mes anterior está vencida más allá de la fecha de gracia y `has_penalty = true`.

### Gastos Recuperables
- Se incluyen automáticamente los gastos (`ContractExpense`) pagados por la agencia (`paid_by = agency`), marcados como `is_paid = true`, y aún no cobrados (`included_in_collection = false`) para el mes correspondiente.

---

## 🔎 Funcionalidades del Sistema

- **Preview de generación mensual**: permite ver un resumen de contratos procesables, bloqueados o ya generados (`GET /collections/preview`).
- **Generación automática**: proceso transaccional que crea cobranzas e ítems (`POST /collections/generate`).
- **Creación manual**: carga de cobranzas individuales (`POST /collections`).
- **Visualización**: listado paginado con filtros, orden y relaciones (`GET /collections`).
- **Detalle**: incluye ítems, cliente, contrato y metadatos (`GET /collections/{id}`).
- **Marcar como pagada**: endpoint para registrar pagos y aplicar lógica contable (`POST /collections/{id}/mark-as-paid`).

---

## ✅ Validaciones Incluidas
- No se permite generar una cobranza si falta una anterior.
- No se generan ajustes sin valor definido.
- Los gastos ya cobrados no se duplican.
- Los ítems deben tener monto positivo.
- La moneda de cada ítem coincide con la de la cobranza.

---

## 🧪 Test Funcional
`CollectionIncludesExpensesTest` garantiza que los gastos pagados por la agencia sean incluidos correctamente al generar la cobranza del mes.

---

## 🧠 Observaciones Técnicas
- Uso de `mainTenant()` para determinar el inquilino principal.
- Toda la lógica de generación está encapsulada en `CollectionGenerationService`.
- El servicio es transaccional y garantiza consistencia.
- Se utiliza `Contract::activeDuring($period)` para determinar contratos válidos por mes.