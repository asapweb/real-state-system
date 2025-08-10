
# Gestión y Validación de Contract Expenses

## 1. Estados del gasto (`ContractExpenseStatus`)
- **pending**: Gasto registrado sin impacto financiero.
- **validated**: Pago validado (ej: tenant→tenant con comprobante adjunto).
- **billed**: Imputado en FAC/N/D (tenant→owner o agency→tenant).
- **credited**: Compensado mediante NC (tenant→owner).
- **liquidated**: Incluido en un voucher LIQ (gastos owner→owner o tenant→owner ya compensados).
- **canceled**: Anulado, sin impacto posterior.

---

## 2. Combinaciones `paid_by` y `responsible_party`

### a) Tenant → Tenant
- **Flujo**: Registro de gasto pagado por el inquilino y a su cargo.
- **Estados**: `pending` → (comprobante adjunto y validación manual) → `validated`.
- **Impacto financiero**: Solo control administrativo. No genera vouchers.

### b) Tenant → Owner
- **Flujo**: El inquilino paga un gasto que correspondía al propietario.
- **Estados**:
  - `pending` → `validated` (validación comprobante).
  - Luego: Generar **NC** (credited) para compensar al inquilino.
  - Finalmente: Incluir gasto en **LIQ** para descontarlo al propietario.
- **Impacto financiero**: NC en cuenta corriente del inquilino y deducción en liquidación.

### c) Owner → Tenant
- **Flujo**: El propietario paga un gasto que correspondía al inquilino.
- **Estados**:
  - `pending` → `billed` (FAC/N/D al inquilino).
- **Impacto financiero**: Aumenta deuda del inquilino, luego cancelable en RCB.

### d) Agency → Tenant
- **Flujo**: La inmobiliaria paga por el inquilino (recupera el gasto).
- **Estados**: `pending` → `billed` (FAC/N/D al inquilino).
- **Impacto financiero**: Similar a c), pero con origen agency.

### e) Agency → Owner
- **Flujo**: La inmobiliaria paga por el propietario.
- **Estados**: `pending` → `liquidated` (incluido en LIQ).
- **Impacto financiero**: Se descuenta en la liquidación.

---

## 3. Campo `effective_date`
- Representa la fecha en la que el gasto corresponde (ej. mes de expensa).
- Usado para agrupar gastos en procesos de facturación o liquidación.

---

## 4. Reglas de edición y bloqueo

- **is_locked**: `billed`, `credited`, `liquidated` → No editables.
- **validated**:
  - Editable en datos administrativos (monto, fechas, descripción).
  - **No se permite cambiar** `paid_by` ni `responsible_party`.

Validación en `UpdateContractExpenseRequest`:
```php
if ($expense->status === ContractExpenseStatus::VALIDATED) {
    $rules['paid_by'] = ['prohibited'];
    $rules['responsible_party'] = ['prohibited'];
}
```

---

## 5. Flujos automáticos pendientes de implementar

1. **Tenant→Tenant**: Validación manual (adjuntar comprobante → cambiar estado a `validated`).
2. **Tenant→Owner**:
   - Validación comprobante (`validated`).
   - Generar **NC** (`credited`).
   - Incluir en LIQ (`liquidated`).
3. **Owner→Tenant**: Facturación FAC/N/D (`billed`).
4. **Agency→Tenant**: Similar a 3.
5. **Agency→Owner**: Incluir en LIQ (`liquidated`).

---

## 6. Próximos pasos

### Paso actual:
- Implementar flujos de cambio de estado en API (NC y LIQ incluidas).
- Ajustar front para disparar estos flujos desde la tabla (acciones contextuales).

### Luego:
- Integrar con generación de vouchers (FAC/N/D, NC, LIQ).
- Ajustar formulario: Bloqueo dinámico de campos según estado.
