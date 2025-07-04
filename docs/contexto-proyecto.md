# 📘 Contexto Operativo del Proyecto - Sistema de Gestión Inmobiliaria (CASSA)

Este archivo define las **decisiones clave**, **convenciones** y **contexto funcional** que deben mantenerse sincronizados entre el equipo de desarrollo (via Cursor), el analista funcional (vos) y ChatGPT. Es el punto de referencia único para entender el funcionamiento global del sistema y evitar ambigüedades.

---

## 🔁 Flujos Críticos

### Cobranzas Mensuales
- Generadas por contrato activo, por cada moneda involucrada.
- Incluyen alquiler base, ajustes, comisiones, gastos recurrentes, servicios y bonificaciones.
- **No incluyen punitorios** (los intereses por mora se calculan al momento de emitir el recibo).
- Se valida continuidad mensual: no se permite generar agosto si falta julio.
- Se permite prorrateo en primer y último mes (`prorate_first_month`, `prorate_last_month`).

### Cobranzas Manuales
- Pueden estar asociadas o no a un contrato.
- Se utilizan para recuperar gastos excepcionales, cobrar conceptos no incluidos en la mensual, etc.
- El usuario define fecha de emisión, vencimiento, cliente y conceptos.

### Recibos y Punitorios
- Los intereses por mora se calculan **al emitir el recibo**, según días vencidos desde el vencimiento.
- Dos métodos posibles:
  - Desde el 1 del mes.
  - Desde el primer día posterior al vencimiento (más frecuente).

---

## 🧠 Convenciones de Desarrollo

### Laravel (API)
- Laravel 11.
- Controladores manuales, no se usa `apiResource` por defecto.
- Rutas bajo prefijo `api/`.
- Modelos en inglés (`Contract`, `Property`, etc.).
- `store()` devuelve `201 Created`, `update()` devuelve `200 OK` con el recurso.
- Enums usados en `$casts`, `CollectionItemType`, `ContractAdjustmentType`, etc.
- Tests con sintaxis moderna: `#[Test]`.

### Vue (Frontend)
- Vue 3 + Vuetify 3.
- `v-data-table-server` en todas las tablas (con filtros, orden, paginación).
- Carpeta `views/{entidad}` con estructura: `Index`, `Create`, `Show`, `Update`.
- Formularios reactivos con `Vuelidate`, `reactive`, `emit('submit')`.
- Selectores externos cargan desde API en `onMounted()`.

### Nombres
- Carpetas y rutas: `kebab-case`.
- Componentes: `PascalCase` (ej. `ClientForm.vue`, `ContractTable.vue`).
- Enums y constantes: `SCREAMING_SNAKE_CASE` o `PascalCase` según contexto.

---

## ⚙️ Consideraciones Técnicas

### Monedas
- Todos los importes deben tener campo `currency` (`ARS`, `USD`, etc.).
- Las cobranzas y gastos se agrupan por moneda.
- Se puede tener más de una cobranza por contrato en un mismo mes si hay múltiples monedas.

### Gastos
- `ContractExpense`: gasto asociado a contrato.
- Campo `paid_by`: `tenant`, `owner`, `agency`.
- Si `paid_by = agency`, **no se incluye** en la cobranza.
- Si se incluye, se genera `CollectionItem` tipo `expense`.

### Bonificaciones
- Se pueden aplicar manualmente, por servicios pagados, por expensas extraordinarias, etc.
- Siempre generan `CollectionItem` negativo.

### Ajustes
- Contratos pueden tener uno o más ajustes (`ContractAdjustment`).
- Tipos: `fixed`, `percentage`, `index`, `negotiated`.
- Los ajustes con valor vencido no cargado bloquean la generación de la cobranza mensual.
- Se deben marcar como `applied` al ser utilizados.

---

## 📂 Archivos que deben mantenerse sincronizados

| Archivo | Propósito |
|--------|-----------|
| `README.md` | Visión general y guía para nuevos integrantes |
| `docs/contexto-proyecto.md` | Este archivo. Contexto completo de trabajo |
| `docs/flujo-cobranzas.md` | Lógica de generación de cobranzas mensuales |
| `docs/ajustes.md` | Gestión de ajustes de contratos |
| `docs/gastos.md` | Criterios para gastos, bonificaciones, recuperos |
| `docs/punitorios.md` | Cálculo de intereses por mora |
| `docs/liquidaciones.md` | (Pendiente) Liquidaciones a propietarios |

---

## 🔔 Pendientes

- Documentar liquidaciones a propietarios.
- Definir proceso para emitir recibos y aplicar punitorios.
- Estandarizar uso de `meta` en `CollectionItem` y mostrar en UI.

---

Este archivo debe leerse antes de modificar cualquier flujo central o lógica de negocio.