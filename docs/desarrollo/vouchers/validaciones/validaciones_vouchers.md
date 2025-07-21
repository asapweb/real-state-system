# Validaciones Funcionales por Tipo de Comprobante (Voucher)

## Tipos de Comprobantes

| Tipo  | Descripción                    | Afecta Cuenta Corriente | Afecta Caja | Es crédito |
|-------|--------------------------------|--------------------------|-------------|------------|
| FAC   | Factura                        | ✅                       | ❌          | ❌         |
| N/C   | Nota de Crédito                | ✅                       | ❌          | ✅         |
| N/D   | Nota de Débito                 | ✅                       | ❌          | ❌         |
| RCB   | Recibo de Cobranza             | ✅                       | ✅          | ❌         |
| RPG   | Recibo de Pago                 | ✅                       | ✅          | ✅         |
| COB   | Cobranza mensual (alquiler)    | ✅                       | ❌          | ✅         |
| LIQ   | Liquidación a propietario      | ✅                       | ❌          | ❌         |

---

## Validaciones comunes a todos los comprobantes

- El `total` nunca puede ser negativo.
- Los valores de `subtotal_*` y `total` se calculan exclusivamente desde el backend (`VoucherCalculationService`).
- Si hay ítems, deben tener: `quantity > 0`, `unit_price >= 0`, `tax_rate_id` válido.
- Si hay `applications`, deben referenciar comprobantes existentes de tipo contrario (`credit` vs `debit`).
- Si hay `associations`, deben referenciar comprobantes existentes (sin afectar saldo).
- La moneda (`currency`) debe coincidir entre el comprobante y sus pagos o aplicaciones.

---

## FAC, N/C, N/D

### FAC (Factura)

- Requiere al menos un ítem.
- No permite `applications`, `associations` ni `payments`.

### N/C (Nota de Crédito)

- Requiere al menos un ítem o una `application`.
- Usa `voucher_applications` para aplicar a comprobantes de tipo débito (`FAC`, `N/D`, `RCB`).
- No puede aplicarse a comprobantes de crédito (`N/C`, `COB`, `RPG`).
- El total debe coincidir exactamente con la suma de ítems y `applications` (considerando signos).
- No debe tener `payments`.
- Cada aplicación debe respetar el saldo pendiente del comprobante aplicado.

### N/D (Nota de Débito)

- Requiere al menos un ítem.
- Requiere exactamente una `association` a un comprobante de tipo `FAC` (sin importe).
- El total se calcula exclusivamente desde ítems.
- No permite `applications` ni `payments`.

---

## RCB y RPG

- El total se basa exclusivamente en la suma de `payments`.
- `Items` opcionales (ej: punitorios, ajustes).
- `Applications` permiten imputar comprobantes anteriores (de tipo contrario).
- La suma de `items` + `applications` **no puede superar** el total de `payments`.

### Casos especiales

- Recibo sin pagos (total = 0):
  - Válido solo si `applications` compensan exactamente a cero (ej: FAC vs N/C).
- Recibo anticipado:
  - Puede emitirse sin `applications`. Se imputará más adelante.
- Aplicaciones posteriores:
  - Un RCB/RPG ya emitido puede recibir aplicaciones a futuro.

---

## COB (Cobranza mensual)

- Se genera automáticamente o manualmente.
- Requiere al menos un ítem.
- No permite `applications`, `associations` ni `payments`.
- Afecta solo cuenta corriente del inquilino (es comprobante de crédito).

---

## LIQ (Liquidación al propietario)

- Requiere al menos un ítem.
- No permite `applications`, `associations` ni `payments`.
- Afecta cuenta corriente del propietario (es comprobante de débito).

---

## Validación al emitir (`VoucherValidatorService::validateBeforeIssue()`)

### General

- Estado debe ser `draft`.
- Se recalcula el total desde los ítems, pagos y aplicaciones (`VoucherCalculationService`).

### FAC, COB, LIQ, N/D

- Requieren al menos un ítem.

### N/D

- Requiere exactamente una `association`.
- Total > 0.

### N/C

- Requiere al menos un ítem o `application`.
- No puede aplicarse a comprobantes `credit = true`.
- Total debe ser igual a la suma de ítems y aplicaciones.
- No puede tener `payments`.

### RCB / RPG

- Requiere al menos un `payment`.
- `items` y `applications` opcionales.
- Suma de `items + applications` ≤ `payments`.
- Si `total = 0`: las aplicaciones deben compensar exactamente a cero.
- No pueden aplicarse a otros RCB/RPG.
