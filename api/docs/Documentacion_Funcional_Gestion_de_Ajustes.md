
# 📘 Documentación Funcional – Gestión de Ajustes de Contrato

## 🧭 Objetivo

Permitir a la inmobiliaria gestionar, visualizar y controlar los ajustes previstos en los contratos de alquiler, asegurando que:
- Se carguen los valores cuando llega la fecha de ajuste.
- Se apliquen correctamente en la cobranza.
- Se detecten rápidamente omisiones o pendientes.
- Se pueda hacer seguimiento del estado de cada ajuste.

## 🧩 Entidades Involucradas

- `contracts`: contratos activos o históricos.
- `adjustments`: ajustes definidos por contrato (ya existen en el sistema).
- `collections`: cobranzas mensuales generadas.
- `collection_items`: ítems generados en una cobranza.

## 🖥️ Pantalla: Gestión de Ajustes (`AdjustmentIndex.vue`)

### 🔍 Filtros disponibles

- Contrato
- Cliente (inquilino)
- Tipo de ajuste (`fixed`, `percentage`, `index`, `negotiated`)
- Fecha de ajuste (`effective_date`)
- Estado del ajuste:
  - `pending`: aún no llegó la fecha
  - `expired_without_value`: vencido sin valor cargado
  - `with_value`: tiene valor cargado pero aún no aplicado
  - `applied`: ya aplicado en una cobranza

### 📊 Tabla principal

| Contrato | Cliente | Tipo de Ajuste | Fecha efectiva | Valor | Estado | Acciones |
|----------|---------|----------------|----------------|-------|--------|----------|
| #001-A   | Juan Pérez | Índice IPC | 2025-07-01 | - | Vencido sin valor | [Cargar] |
| #002-B   | Familia González | Fijo | 2025-08-01 | $180.000 | Pendiente | [Ver] |
| #003-C   | Locadora S.A. | Negociado | 2025-06-01 | 7% | Aplicado en cobranza | [Ver] |

### 📌 Estados de los ajustes

- `pending`: `effective_date` > hoy y sin valor cargado
- `expired_without_value`: `effective_date` <= hoy y `value` es null
- `with_value`: `value` definido y aún no aplicado
- `applied`: ya aplicado en una cobranza (`applied_at` o trazabilidad)

### 🛠 Acciones por ajuste

- Ver detalle del contrato
- Cargar o editar valor del ajuste
- Agregar observaciones
- Ver cobranza donde se aplicó (si ya fue usada)
- Marcar como revisado

## 🔔 Alertas automáticas

El sistema puede detectar ajustes `expired_without_value` y:
- Notificar al usuario en la interfaz.
- Ofrecer un botón de acceso rápido para cargarlos.
- En el futuro, enviar alertas por email o WhatsApp interno.

## 🧪 Casos de uso frecuentes

1. **Ajustes por índice (IPC, UVA):**
   - Se define el ajuste al crear el contrato.
   - El sistema recuerda la fecha y avisa si falta cargar el valor.
   - Al cargarse, se usa automáticamente en la siguiente cobranza.

2. **Ajustes fijos o negociados:**
   - El usuario puede definirlos por anticipado.
   - El sistema controla su aplicación.

3. **Auditoría ante reclamos:**
   - Desde la vista puede verse cuándo se aplicó un ajuste, en qué período y con qué valor.

## 🧠 ¿Por qué collections y collection_items?

- `collections` representa la cobranza mensual generada para un contrato en un período.
- `collection_items` permite saber si un ajuste fue efectivamente aplicado dentro de esa cobranza.
- Ambas entidades son necesarias para auditar y automatizar correctamente la gestión de ajustes.
