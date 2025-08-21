# 📑 Documento Funcional -- Gestión de Cobranzas, Cargos y Recuperos

## 1. Unificación de `contract_expenses` → `contract_charges`

-   Se reemplaza el modelo `contract_expenses` por `contract_charges`.
-   `contract_charges` incluye **todos los cargos asociados al
    contrato**:
    -   **Renta** (mensual o prorrateada).
    -   **Gastos trasladables** (expensas, impuestos, servicios).
    -   **Recuperos** (propietario, inquilino, inmobiliaria).
    -   **Bonificaciones / descuentos**.
-   Cada cargo tiene un **tipo** (`contract_charge_types`), que define:
    -   Si es **débito o crédito** (`is_credit`).
    -   Si afecta **cobranza (COB)**, **liquidación (LIQ)** o ambas.
    -   Roles por defecto (quién pagó y a quién correspondía).

## 2. Tipos principales de `contract_charges`

-   **RENT**: renta mensual, siempre débito.
-   **OWNER_TO_TENANT**: gasto pagado por propietario que correspondía
    al inquilino → se traslada al COB.
-   **TENANT_TO_OWNER**: gasto pagado por inquilino que correspondía al
    propietario → se refleja en LIQ como crédito.
-   **AGENCY_TO_TENANT**: gasto pagado por inmobiliaria que correspondía
    al inquilino → recupero en COB.
-   **BONIFICATION**: descuento pactado → crédito en COB.

Cada tipo determina por defecto cómo se comporta en COB y LIQ.

## 3. Bonificaciones

-   Si la COB está en **borrador** → la bonificación se incluye como
    cargo de tipo crédito dentro de la factura interna X.
-   Si la COB ya fue **emitida** → se genera **Nota de Crédito interna
    (NC_INT)** vinculada a esa COB.
-   Si hay factura fiscal emitida, además → se genera **NC fiscal**.

## 4. Recuperos de la inmobiliaria (AGENCY → TENANT)

-   La inmobiliaria paga un gasto (ej. ABSA) → debe **registrarse el
    egreso real**.
-   Se genera un `contract_charge` de tipo **AGENCY_TO_TENANT**.
-   El sistema crea automáticamente un **PAG_INT** (recibo interno de
    pago) que:
    -   Registra el movimiento de egreso en caja/banco.
    -   Queda vinculado al `contract_charge`.
-   En la siguiente COB (si está en borrador) se incluye.
-   Si la COB ya está emitida → se genera **COB complementaria** o **ND
    interna**.
-   No afecta la liquidación al propietario.

## 5. Flujo de trabajo mensual

1.  **Ajustes de contrato**: aplicación masiva o individual de índices →
    actualiza renta base.
2.  **Generación de rentas**: se crean `contract_charges` de tipo RENT
    por cada contrato activo.
3.  **Generación de COB**:
    -   El sistema toma todos los `contract_charges` del período
        (rentas, gastos, recuperos, bonificaciones).
    -   Crea un **voucher COB / Factura X interna** con un ítem por cada
        cargo.
    -   Cada cargo queda vinculado al `voucher_id`.

## 6. Casos especiales

-   **OWNER_TO_TENANT**: carga genera cargo en COB (al inquilino) y
    espejo en LIQ (al propietario).
-   **TENANT_TO_OWNER**: carga genera crédito en LIQ (al propietario).
-   **AGENCY_TO_TENANT**: carga genera egreso en caja + cargo pendiente
    de COB.
-   **Bonificación**: en COB borrador → ítem negativo; en COB emitida →
    NC_INT.

## 7. Estados de los `contract_charges`

-   `pending`: cargado pero no incluido en ningún voucher.
-   `included`: asociado a COB/LIQ en borrador.
-   `billed`: COB/LIQ emitida.
-   `canceled`: revertido por anulación del voucher.
-   `pending_egress`: especial para AGENCY_TO_TENANT → hasta que tenga
    movimiento de egreso.

## 8. Evolución futura

-   **Etapa 1 (implementación inicial)**: `PAG_INT` automático en la
    carga de AGENCY_TO_TENANT.
-   **Etapa 2 (opcional)**: posibilidad de registrar **REC_PAG** manual
    (pago a proveedor) → desde ahí generar el `contract_charge` de
    recupero.
-   Este cambio no afecta el modelo, solo la forma de carga.

------------------------------------------------------------------------

📌 Con esto, dejamos consolidado: - Cómo se manejan rentas, gastos,
recuperos y bonificaciones. - Cómo interactúan COB, LIQ y NC. - Qué se
exige en cada tipo (ej. egreso obligatorio en AGENCY_TO_TENANT). - Qué
caminos de evolución están previstos.
