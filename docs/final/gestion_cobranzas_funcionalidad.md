# Documento Funcional: Gestión de Cobranzas Mensuales

## Objetivo
Permitir al operador gestionar de forma centralizada todas las cobranzas mensuales de los contratos activos, incluyendo alquileres, gastos asociados y punitorios, contemplando multimoneda, estados previos y flexibilidad operativa.

---

## 1. Conceptos involucrados

### 1.1. Tipos de comprobante
- **ALQ**: Resumen de alquiler. Unico por contrato y período. Incluye el alquiler base y opcionalmente gastos.
- **COB**: Recibo de cobranza. Permite cobrar alquileres impagos, punitorios y gastos nuevos ya devengados.
- **FAC X**: Factura interna. Permite devengar conceptos adicionales al alquiler (gastos, punitorios, otros). Se aplica luego mediante un COB. Ya existe en el sistema como tipo de comprobante y booklet.

### 1.2. Conceptos cobrables
- Alquiler mensual (calculado según contrato, ajustes y bonificaciones)
- Gastos (`contract_expenses`) marcados como `included_in_collection = true`
- Punitorios por mora (calculados en base a la fecha actual y fecha de vencimiento)

---

## 2. Flujo general

### 2.1. Visualización consolidada por contrato y moneda
- Tabla que muestra todos los conceptos cobrables, agrupados por moneda.
- Cada fila indica:
  - Tipo: Alquiler, Gasto, Punitorio
  - Descripción
  - Período
  - Monto sugerido (calculado por el sistema)
  - Monto final (editable por el operador)
  - Estado actual (No emitido, ALQ draft, ALQ emitido, etc.)

### 2.2. Estados posibles

#### Caso 1: Nada generado
- Seleccionables todos los conceptos.
- Al hacer clic en "Generar", se crea un ALQ con todos los conceptos en un único voucher (por moneda).

#### Caso 2: ALQ en draft
- Mostrar voucher existente.
- Permitir agregar gastos no incluidos al mismo ALQ draft.

#### Caso 3: ALQ emitido
- Mostrar ALQ y sus contenidos.
- Para nuevos gastos o punitorios:
  - Generar una **FAC X** con los conceptos adicionales.
  - Luego, emitir un **COB** aplicando tanto el ALQ como la FAC X.

#### Caso 4: ALQ emitido parcialmente cobrado
- Mostrar ALQ con saldo pendiente.
- Permitir aplicar un COB para cubrir saldo y nuevos conceptos devengados mediante FAC X.

---

## 3. Lógica de selección y generación

### 3.1. Agrupamiento por moneda
- No se permiten vouchers con conceptos en diferentes monedas.
- El sistema agrupará por moneda y generará un comprobante por cada una.

### 3.2. Generación de comprobantes
- Si hay ALQ draft existente para la moneda: se agregarán los conceptos seleccionados.
- Si no hay ALQ: se creará uno nuevo.
- Si ALQ ya fue emitido:
  - Se generará una FAC X con los conceptos no devengados.
  - Luego, se podrá generar un COB que aplique ALQ + FAC X.

### 3.3. Edición de montos
- Todos los importes sugeridos son editables antes de confirmar.
- Si un importe se modifica manualmente, se registra esa acción.

### 3.4. Cálculo de punitorios
- Se muestran automáticamente si corresponde (ALQ vencido y saldo > 0).
- Calculados desde la fecha de vencimiento al día actual.
- Deben emitirse mediante una FAC X antes de cobrarse.
- Pueden editarse o eliminarse antes de generar el COB.

---

## 4. Acciones del usuario

- Ver estado general del contrato por período y moneda
- Seleccionar conceptos a incluir en un comprobante
- Editar montos finales de cada concepto
- Generar comprobantes ALQ, FAC X o COB según corresponda
- Ver historial de comprobantes anteriores (emitidos, borradores, pagos parciales)

---

## 5. Consideraciones técnicas

- Los `contract_expenses` se consideran pendientes si:
  - `included_in_collection = true`
  - `voucher_id IS NULL`
- Los `voucher_items` deben reflejar tipo (`rent`, `expense`, `penalty`, etc.)
- El `voucher_type` define si se afecta cuenta corriente y/o caja.
- Se debe validar unicidad de ALQ por contrato y período.
- Se requiere soporte multimoneda en la interfaz y en los vouchers generados.
- Los COB no deben contener ítems no devengados previamente.
- Los gastos y punitorios fuera del ALQ deben ir en una **FAC X** y aplicarse al COB.
- El comprobante FAC X ya existe en el sistema como tipo `FAC`, letra `X`, `affects_account = true`, `affects_cash = false`.

---

## 6. Wireframes relacionados

- Estado de cuenta por contrato
- Generar recibo de cobranza (tabla editable con montos y comprobantes)

---

## 7. Pendientes a definir

- Comportamiento ante gastos en disputa o no cobrables
- Generación automática de punitorios con tolerancia de días
- Visualización de total por contrato y saldo acumulado
- Integración con liquidaciones a propietarios

