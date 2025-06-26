## Modelo Unificado de Comprobantes (`vouchers`)

Este documento registra las decisiones funcionales y técnicas sobre la implementación de un modelo único de comprobantes en el sistema de gestión inmobiliaria, unificando cobranzas, pagos, facturas, notas de crédito/débito y otros documentos contables.

---

### ✅ Objetivo
Unificar todos los tipos de comprobantes contables (internos y legales) en una estructura centralizada que permita:
- Control de numeración y talonarios
- Generación de movimientos de caja y cuenta corriente
- Preparación para factura electrónica (AFIP)
- Simplificación de lógica contable

---

### 📝 Entidades principales

#### 1. `books` (Talonarios)

| Campo               | Tipo       | Comentario                             |
|---------------------|------------|----------------------------------------|
| `id`                | bigint     | PK                                     |
| `name`              | string     | Ej: "Recibos Internos X"               |
| `type`              | enum       | `charge`, `receipt`, `payment`, `invoice`, etc.  |
| `letter`            | enum       | `X`, `A`, `B`, `C`                      |
| `next_number`       | int        | Correlativo                            |
| `is_default`        | bool       | Talonario por defecto                  |
| `is_electronic`     | bool       | Si requiere CAE/AFIP                   |
| `afip_point_of_sale`| int|null   | Punto de venta AFIP (si aplica)        |

---

#### 2. `vouchers`

| Campo                  | Tipo       | Comentario                                    |
|------------------------|------------|-----------------------------------------------|
| `id`                   | bigint     | PK                                            |
| `book_id`              | FK         | Talonario asociado                           |
| `number`               | int        | Número correlativo del talonario             |
| `type`                 | enum       | Redundante para acceso rápido (`from book`)  |
| `client_id`            | FK|null    | Cliente involucrado                           |
| `provider_id`          | FK|null    | Proveedor (si aplica)                         |
| `date`                 | date       | Fecha de emisión                              |
| `due_date`             | date|null  | Fecha de vencimiento                          |
| `currency`             | string     | Ej: ARS, USD                                  |
| `total`                | decimal    | Total del comprobante                         |
| `status`               | enum       | `draft`, `issued`, `canceled`, etc.           |
| `affects_account_balance` | bool   | Si genera movimiento en cta cte               |
| `has_cash_movement`    | bool       | Si genera movimiento de caja                  |
| `related_voucher_id`   | FK|null    | Referencia a comprobante origen (ej. recibo)  |
| `meta`                 | json       | Info variable (CAE, periodo, etc.)            |
| `created_at`           | timestamp  |                                               |

---

#### 3. `voucher_items`

| Campo              | Tipo     | Comentario                             |
|--------------------|----------|----------------------------------------|
| `voucher_id`       | FK       | Comprobante padre                      |
| `type`             | enum     | `rent`, `commission`, `payment`, etc. |
| `description`      | string   |                                        |
| `quantity`         | int      |                                        |
| `unit_price`       | decimal  |                                        |
| `amount`           | decimal  |                                        |
| `related_model_id` | morph    | Asociación con gasto, contrato, etc.  |
| `meta`             | json     |                                        |

---

### 🔍 Reglas funcionales clave

- Las `collections` actuales se representarán como `vouchers` con:
  - `type = 'charge'`
  - `book.type = 'charge'`
  - `book.letter = 'X'`
  - `affects_account_balance = true`
  - `has_cash_movement = false`

- Los `receipts` representan pagos reales:
  - `type = 'receipt'`
  - `affects_account_balance = true`
  - `has_cash_movement = true`

- Las `invoices` (facturas) generan deuda:
  - Solo si son el origen de la deuda.
  - Si son emitidas luego de la cobranza, deben tener `affects_account_balance = false`.

- Las `payments` (recibos de pago a proveedores o propietarios):
  - Cancelan deuda generada por `liquidations` o `expenses`
  - `has_cash_movement = true`

---

### ⚡ Ejemplo de flujo

1. Se genera una cobranza mensual: `voucher` tipo `charge`, sin movimiento de caja.
2. El cliente paga: se crea `voucher` tipo `receipt`, que referencia al anterior, y genera ingreso de dinero.
3. El cliente solicita factura: se emite `voucher` tipo `invoice`, con `affects_account_balance = false`.

---

### ✅ Conclusión

El modelo único de `vouchers` permite unificar toda la lógica de comprobantes contables, facilitar reportes, simplificar caja y cuenta corriente, y preparar el sistema para integraciones fiscales futuras (AFIP). La separación entre tipos como `charge` (cobranza) y `receipt` (pago) asegura precisión contable y semántica clara.

