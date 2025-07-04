# 🎨 Arquitectura del Frontend (Vue)

Este documento define la estructura, patrones y buenas prácticas para el desarrollo del frontend del sistema inmobiliario, implementado en **Vue 3** con **Vuetify 3**. Sirve como guía para desarrolladores, asistentes de IA como ChatGPT y herramientas como Cursor.

---

## 🌐 Stack Tecnológico

- **Framework**: Vue 3 (Composition API)
- **UI**: Vuetify 3
- **Bundler**: Vite
- **Store**: Pinia
- **Ruteo**: Vue Router
- **Testing**: Vitest
- **Formato**: JavaScript con persistencia de estado (`pinia-plugin-persistedstate`)

---

## 📁 Estructura del Proyecto

```
front/
├── views/               # Vistas por entidad
│   └── clients/
│       ├── ClientIndex.vue
│       ├── ClientCreate.vue
│       ├── ClientShow.vue
│       ├── ClientUpdate.vue
│       └── components/
│           ├── ClientForm.vue
│           └── ClientTable.vue
├── components/          # Componentes reutilizables globales
├── services/            # Llamadas a la API agrupadas por recurso
├── stores/              # Stores de Pinia
├── utils/               # Helpers y formatters
├── App.vue
└── main.js
```

---

## 🧩 Patrones de Componentes

### Contenedor / Presentación

- **`ContractIndex.vue`**: contiene layout, filtros, navegación
- **`ContractTable.vue`**: renderiza tabla de resultados
- **`ContractForm.vue`**: formulario con validaciones
- **`ContractAutocomplete.vue`**: selector con búsqueda y opción de crear

---

## 🧪 Testing

- Tests con Vitest
- Cobertura recomendada para:
  - Comportamiento del formulario
  - Acciones críticas de usuario
  - Reglas de visualización condicional
- Ubicación sugerida: `tests/unit/{componente}.spec.js`

---

## 🧠 Convenciones

| Elemento              | Convención     | Ejemplo                    |
|-----------------------|----------------|----------------------------|
| Carpetas              | `kebab-case`   | `contract-expenses/`       |
| Archivos Vue          | `PascalCase`   | `ContractForm.vue`         |
| Componentes globales  | `PascalCase`   | `ConfirmDialog.vue`        |
| Rutas Vue Router      | `kebab-case`   | `/contracts/:id/edit`      |
| Variables CSS         | `kebab-case`   | `--form-spacing-sm`        |

---

## 🔗 Comunicación con la API

- Se usa `axios` configurado en `/services/axios.js`
- Token se establece globalmente con:
  ```js
  axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
  ```
- Todas las llamadas se organizan en `/services/{entidad}.js`
- El frontend espera respuestas en formato:
  ```json
  {
    "data": [...],
    "meta": {...},
    "links": {...}
  }
  ```

---

## 📝 Formularios

- Composición con Composition API (`ref`, `reactive`)
- Validaciones con **Vuelidate**
- Eventos emitidos:
  - `submit`
  - `cancel`
- Soporte para campos condicionales (ej: `no_document`)
- Campos selectivos cargados dinámicamente desde la API

---

## 🧠 Estado con Pinia

- Estado local por defecto
- Usar Pinia **solo si**:
  - El estado es compartido entre vistas
  - O la lógica del estado es compleja (ej: `authStore`)
- Persistencia habilitada solo en stores críticos (`auth`)

---

## 🔎 Convenciones de Tablas

- Uso obligatorio de `v-data-table-server`
- Filtros con `v-text-field` + debounce
- Botón de acciones con `v-menu` + ítems (ver, editar, eliminar)
- Paginación y ordenamiento siempre del lado del servidor

---

## 🧩 Helpers Reutilizables

- Ubicados en `utils/`
- Ejemplos:
  - `formatDate()`
  - `formatModelId()`
  - `formatMoney()`
  - `statusColor()`, `formatPeriod()` para cobranzas

---

Este archivo debe mantenerse alineado con el `ARCHITECTURE.md` raíz y representa las reglas internas del frontend.
