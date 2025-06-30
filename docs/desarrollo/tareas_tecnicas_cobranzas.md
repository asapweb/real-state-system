
# 🛠️ Tareas Técnicas: Generación de Cobranzas Mensuales (actualizado al 28/06/2025)

Este documento resume las tareas técnicas necesarias y su estado actual, en base al desarrollo realizado hasta hoy.

---

## ✅ Prioridad Alta (completadas o en producción)

### 1. ✅ Cálculo de punitorios por mora
- Se implementó `Contract::calculatePenaltyForPeriod()`.
- Ya se generan ítems de tipo `penalty` automáticamente.
- **Estado:** Funcionalidad validada y en uso.

### 2. ✅ Integración automática de servicios (recuperos)
- Se incluyen automáticamente `ContractExpense` con:
  - `paid_by = agency`
  - `is_paid = true`
  - `included_in_collection = false`
- Se registran como ítems `service` y se marca `included_in_collection = true`.
- Soporta multimoneda y regeneración parcial.
- **Estado:** Completado y en producción.

### 4. ✅ Pagos combinados y observaciones
- Soporte parcial implementado: campo `meta` en `Collection` e `Item` permite observaciones personalizadas.
- El registro explícito de pagos mixtos está sujeto a definición contable externa.
- **Estado:** Estructura lista para extender.

---

## 🔁 En curso / en análisis

### 3. 🔁 Soporte para bonificaciones
- Falta definir modelo y UI.
- Se implementará como `CollectionItem` negativo (`amount < 0`).
- Tipos posibles: acuerdos con propietarios, expensas extraordinarias, descuentos temporales.
- **Estado:** En análisis funcional y técnico.

---

## 🟡 Prioridad Media (no iniciado)

### 5. Redondeo automático de alquiler ajustado
- Requiere:
  - Campo `rounding_rule` por contrato o configuración global.
  - Aplicación al momento de calcular el ajuste.
- **Estado:** No iniciado.

### 6. Alerta por renovación de seguros
- Requiere modelo `ContractInsurance` o fecha de vencimiento de póliza en `contracts`.
- Agregar notificación automática X días antes del vencimiento.
- **Estado:** No iniciado.

### 7. Comunicación automática (WhatsApp / Email)
- Envío automático de mensaje al generar cobranza con:
  - Monto total
  - Detalles de ítems
  - Link o PDF del recibo
- Requiere integración externa.
- **Estado:** Pendiente de definición técnica.

---

## 🟠 Prioridad Baja (mejoras a futuro)

### 8. Proporcionalidad por días
- Ya se implementaron:
  - `prorate_first_month`
  - `prorate_last_month`
- Falta soporte para:
  - Vacancia parcial
  - Cambio de inquilino en el mes
- **Estado:** Parcialmente resuelto.

### 9. Checklist de documentación pendiente
- Agregar alertas visuales en cuenta corriente o ficha de cliente si:
  - No presentó seguro
  - No retiró contrato
  - Falta cambio de titularidad, etc.
- Requiere entidad auxiliar (`ClientChecklist`).
- **Estado:** No iniciado.

---

## ✔️ Conclusión

El sistema ya cuenta con una **implementación robusta y funcional** para:

- Generación automática por contrato y por moneda
- Inclusión de ajustes, penalidades y gastos
- Cancelación y regeneración por moneda
- Control de estado previo a la generación

**Próximo paso sugerido:** cerrar la interfaz de trazabilidad completa de cobranzas (ítems, `meta`, regeneración), y luego avanzar con:

- Módulo de **liquidaciones a propietarios**, o
- Módulo de **pagos / caja**

según prioridad del cliente.
