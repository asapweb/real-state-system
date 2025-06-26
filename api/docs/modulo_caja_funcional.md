**Documento funcional: Módulo de Caja**

---

## Objetivo

Registrar y controlar todos los movimientos financieros de la inmobiliaria, incluyendo ingresos y egresos de caja física y de cuentas operativas (bancarias, virtuales, etc.), permitiendo trazabilidad completa del flujo de fondos.

---

## Alcance

El módulo contempla:
- Caja física con apertura y cierre diario.
- Cuentas operativas sin apertura/cierre (bancos, cuentas virtuales).
- Registro de todos los movimientos financieros, automáticos o manuales.
- Asociación a pagos, cobranzas u otros conceptos.

---

## Entidades involucradas

### `accounts`
- Representan las cuentas disponibles (Caja principal, bancos, virtuales).
- Campos clave: `id`, `name`, `type` (`cash`, `bank`, `virtual`), `is_cash`.

### `cashboxes`
- Ciclo diario de caja física.
- Solo para cuentas de tipo `cash`.
- Campos clave: `id`, `account_id`, `opened_at`, `closed_at`, `initial_balance`, `final_balance`, `opened_by`, `closed_by`.

### `cashbox_movements`
- Todos los movimientos financieros.
- Campos clave:
  - `type`: `income` o `expense`
  - `amount`, `description`
  - `related_type`, `related_id`: referencia a `payment`, `collection` o null para manuales
  - `account_id`: cuenta utilizada
  - `cashbox_id`: solo para cuentas `cash` con caja abierta
  - `payment_method_id`, `created_by`

### `payments`
- Recibos de pago, generan egresos.
- Asociados a gastos, liquidaciones, servicios, etc.

### `collections`
- Recibos de cobranza, generan ingresos.
- Asociados a contratos y clientes.

### `payment_methods`
- Efectivo, transferencia, cheque, etc.

---

## Reglas funcionales

1. **Todos los movimientos se registran en `cashbox_movements`**.

2. Si el movimiento usa una cuenta de tipo `cash`:
   - Requiere que la caja (`cashbox`) esté abierta.
   - Se asocia al `cashbox_id` correspondiente.

3. Si el movimiento usa cuenta `bank` o `virtual`:
   - No requiere caja abierta.
   - `cashbox_id` queda en `null`.

4. Las cobranzas generan movimientos de ingreso asociados a `collection`.

5. Los pagos generan movimientos de egreso asociados a `payment`.

6. Los movimientos manuales se cargan directamente en `cashbox_movements` sin comprobante externo.
   - Pueden tener descripción, adjuntos y `related_type = null`.

---

## Criterios para apertura y cierre de caja

- Aplica solo a cuentas de tipo `cash`.
- Cada caja física debe ser abierta al iniciar el día y cerrada al finalizar.
- Solo se pueden registrar movimientos con `cashbox_id` en cajas abiertas.
- El cierre debe validar saldo final, diferencias y observaciones.

---

## Temas a validar con el cliente

1. ¿Usan múltiples cuentas operativas (bancos, virtuales, etc.)?
2. ¿Quieren registrar movimientos entre cuentas internas?
3. ¿Desean reflejar pagos por transferencia como egresos aunque no pasen por caja física?
4. ¿Utilizan comprobantes internos (vale de egreso, reintegros) para movimientos manuales?

---

## Observaciones

- Este diseño permite unificar en una sola vista todos los movimientos financieros.
- La caja física mantiene su ciclo diario sin interferir con las operaciones digitales.
- Se garantiza la trazabilidad entre recibos, cuentas y movimientos.

