
# Resumen: Cálculo de Liquidaciones al Propietario

## 1️⃣ Liquidación al propietario (LIQ)
La liquidación al propietario se calcula a partir de:
- **Ingresos base:** Ítems de renta y gastos (FAC/N/D) liquidados al propietario.
- **Bonificaciones:** N/C que reducen renta del propietario.
- **Gastos a cargo del propietario:** `contract_expenses` pagados por inquilino o inmobiliaria.
- **Comisión de la inmobiliaria:** Calculada según contrato (ej. % sobre renta cobrada).

**Fórmula:**
```
Liquidación neta = (FAC + N/D cobradas)
                 - (N/C aplicadas)
                 - (Gastos a cargo propietario)
                 - (Comisión inmobiliaria)
```

---

## 2️⃣ Vinculación de entidades
- **`voucher` (FAC, N/D, N/C):** Siempre puede vincularse a un `contract_id` y un `period`.
- **`voucher_items`:**
  - Cada ítem indica si **afecta la liquidación al propietario**.
  - Puede vincularse opcionalmente a un `contract_expense` si corresponde a un gasto registrado.

Campos en `voucher_items`:
```php
affects_owner_settlement (boolean)   // Incluye o excluye ítem en liquidación al propietario
contract_expense_id (nullable)       // Referencia a gasto cargado previamente
type (enum: rent, expense, commission, bonus, ...)
```

---

## 3️⃣ Reglas de inclusión/exclusión en liquidación
- **Se liquidan al propietario:**
  - Renta mensual (FAC).
  - Gastos facturados al inquilino que corresponden al propietario.
  - Bonificaciones (N/C) que reducen ingresos del propietario.

- **Se excluyen (ingresos propios de inmobiliaria):**
  - Comisiones de administración.
  - Honorarios de gestión.
  - Servicios no relacionados al contrato.

---

## 4️⃣ Uso de `contract_expenses`
Cada gasto (`contract_expense`) indica:
- **Quién lo pagó:** `paid_by` (tenant, owner, agency).
- **A quién corresponde:** `responsible_party` (owner, tenant).

Impacto en liquidación:
- Pagado por inquilino y corresponde a propietario: Se descuenta en LIQ y genera N/C o crédito.
- Pagado por inmobiliaria y corresponde a propietario: Se descuenta en LIQ (inmobiliaria recupera).
- Pagado por propietario: Informativo, no genera ajuste.

---

## 5️⃣ Notas de crédito al inquilino (N/C)
- Cuando un gasto es **pagado por el inquilino y corresponde al propietario**, se genera automáticamente una **N/C al inquilino**:
  - Si se carga **antes del cobro**, se aplica en el RCB y paga menos.
  - Si se carga **después**, queda crédito para el mes siguiente.

---

## 6️⃣ Facturas sin contrato
- Si `voucher.contract_id` es **null**:
  - Ocultar `affects_owner_settlement` y `contract_expense_id`.
  - En backend: forzar `affects_owner_settlement = false` y `contract_expense_id = null`.

---

## 7️⃣ Backend y queries
Query para liquidación:
```php
VoucherItem::whereHas('voucher', fn($q) =>
    $q->where('contract_id', $contractId)
      ->where('status', 'issued')
      ->whereBetween('issue_date', [$periodStart, $periodEnd])
      ->whereIn('voucher_type_short_name', ['FAC','N/D','N/C'])
)->where('affects_owner_settlement', true)->get();
```

---

## 8️⃣ Comprobantes fiscales al propietario
- **No se emiten N/C o N/D fiscales al propietario.**
- La **liquidación actúa como documento administrativo** justificando ingresos, gastos y neto.

---

## 9️⃣ Flujo ideal de gasto pagado por inquilino
1. Registrar `contract_expense` (paid_by tenant, responsible_party owner).
2. Generar N/C al inquilino.
   - Antes del cobro: se aplica en RCB.
   - Después: queda crédito en cta. cte.
3. En LIQ, descontar el gasto al propietario.

---
