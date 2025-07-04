# 🧱 Contrato de Arquitectura (Frontend ↔ Backend)

Este documento define la arquitectura general del sistema de gestión inmobiliaria y actúa como **contrato entre el backend (Laravel)** y el **frontend (Vue)**. Es el punto central que garantiza interoperabilidad clara, escalabilidad futura y sincronización entre equipos y herramientas como Cursor y ChatGPT.

---

## 🧭 Filosofía General

- Arquitectura desacoplada: **SPA + API RESTful**
- Comunicación sin estado (stateless)
- Intercambio de datos exclusivo en **JSON**
- Separación total entre lógica de backend y frontend

---

## 📁 Estructura del Repositorio

```
real-state-system/
├── api/                # Laravel 11 (backend)
├── front/              # Vue 3 + Vuetify 3 (frontend)
├── docs/               # Documentación funcional y técnica
├── ARCHITECTURE.md     # Este contrato de arquitectura (nivel raíz)
├── api/ARCHITECTURE.md # Reglas específicas del backend
├── front/ARCHITECTURE.md # Reglas específicas del frontend
└── README.md           # Visión general del proyecto
```

---

## 🔗 Contrato de la API

### Endpoint Base

```
https://<dominio>.ddev.site/api/v1
```

### Autenticación

- Laravel Sanctum en modo **token Bearer**
- El frontend hace login con `POST /api/login` y recibe un token
- El token se guarda en el frontend (Pinia) y se envía en cada request como:
  ```
  Authorization: Bearer <token>
  ```
- **No se utilizan cookies ni CSRF tokens**

> En el futuro, si se centraliza dominio y se desea usar sesiones, se puede evaluar el modo SPA con cookies (`/sanctum/csrf-cookie` + `AuthenticateSession`)

### Formato de Datos

#### Respuesta Exitosa (Listado paginado)

```json
{
  "data": [...],
  "links": {...},
  "meta": {...}
}
```

#### Respuesta Exitosa (Recurso único)

```json
{
  "data": {
    "id": 1,
    "name": "Ejemplo",
    ...
  }
}
```

#### Respuesta de Error

```json
{
  "message": "Error validando datos.",
  "errors": {
    "email": ["El campo es obligatorio."]
  }
}
```

---

## 📛 Convenciones de Nombres

| Elemento             | Convención     | Ejemplo                   |
|----------------------|----------------|---------------------------|
| Endpoints API        | `kebab-case`   | `/contract-adjustments`   |
| Modelos Laravel      | `PascalCase`   | `ContractAdjustment`      |
| Componentes Vue      | `PascalCase`   | `ContractForm.vue`        |
| Carpetas             | `kebab-case`   | `contract-adjustments/`   |
| Rutas frontend       | `kebab-case`   | `/contracts/:id/edit`     |
| Clases de Enum       | `PascalCase`       | `ContractAdjustmentType`         |
| Valores del Enum     | `SCREAMING_SNAKE_CASE` | `FIXED`, `PERCENTAGE`             |

---

## 🧪 Testing

- Backend: PHPUnit con atributos `#[Test]`
- Frontend: Vitest

---

## 🧰 Entorno de Desarrollo

### Requisitos

- PHP 8.3, MySQL 8, Node 20+
- Docker + DDEV

### Setup Rápido

```bash
# Iniciar entorno
ddev start

# Backend
cd api
composer install
cp .env.example .env
php artisan migrate:fresh --seed

# Frontend
cd ../front
npm install
npm run dev
```

---

## 📎 Notas Finales

Este archivo debe mantenerse sincronizado cuando se modifiquen flujos que involucren a ambos lados (front y back).  
Las implementaciones internas se detallan en:

- `api/ARCHITECTURE.md`
- `front/ARCHITECTURE.md`
