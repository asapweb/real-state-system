# Arquitectura del Sistema

## 📂 Estructura General del Monorepo

El sistema inmobiliario está organizado en un **repositorio unificado (monorepo)** con tres áreas principales:

- **`api/` – Backend**  
  Proyecto en **Laravel 11**, expuesto principalmente como API REST.  
  Incluye: migraciones, modelos, controladores, servicios de negocio, factories/tests con PHPUnit, y manejo de autenticación con Sanctum.  

- **`front/` – Frontend**  
  Proyecto en **Vue 3 + Vuetify 3**, estructurado por entidades (`views/{entidad}`), con formularios validados por Vuelidate, tablas `v-data-table-server` y componentes reutilizables.  

- **`docs/` – Documentación funcional y técnica**  
  Archivos en Markdown que describen flujos, entidades, decisiones y especificaciones validadas con el cliente.  

- **Workspace (`real-state-system.code-workspace`)**  
  Configuración predefinida para VS Code que facilita trabajar con backend y frontend en un mismo entorno.  

- **Control de versiones**  
  Repositorio Git unificado, siguiendo convenciones de ramas y commits orientados a features y hotfixes.  

---

## 🔑 Convenciones Generales

- **Lenguaje de modelos y tablas**: inglés (coherente con Laravel y paquetes como Spatie Permissions).  
- **Rutas**: definidas manualmente para mayor flexibilidad. Solo se usa `apiResource` en CRUDs simples.  
- **Respuestas API**:  
  - `201 Created` en `store()` con el recurso creado.  
  - `200 OK` en `update()` con el recurso actualizado.  
  - Nunca se usa `204 No Content`.  
- **Enums**: valores en `SCREAMING_SNAKE_CASE`.  
- **Adjuntos**: manejados con relación polimórfica `attachments()`.  
- **Frontend**:  
  - Vistas bajo `views/{entidad}`.  
  - Convención de nombres en PascalCase (ej. `ClientIndex.vue`, `PropertyForm.vue`).  
  - Componentes globales en `components/`, específicos en `views/{entidad}/components/`.  

---

## 🚀 Flujo de Trabajo General

1. **Backend (`api/`)** expone endpoints REST paginados, con filtros y orden dinámico.  
2. **Frontend (`front/`)** consume estos endpoints con Axios, representando datos en tablas server-side y formularios con validaciones dinámicas.  
3. **Documentación (`docs/`)** registra decisiones funcionales y técnicas validadas.  
4. **Deploys y entornos**:  
   - **Desarrollo**: DDEV sobre Docker, MySQL 8, Node 20.  
   - **Producción**: configuraciones adaptadas según cliente (pendiente de documentación específica).  

---

## 📌 Documentos Relacionados

- [`/api/ARCHITECTURE.md`](api/ARCHITECTURE.md): detalles técnicos del backend Laravel.  
- [`/front/ARCHITECTURE.md`](front/ARCHITECTURE.md): detalles técnicos del frontend Vue + Vuetify.  
- [`/docs/`](docs/): documentación funcional del sistema.  
