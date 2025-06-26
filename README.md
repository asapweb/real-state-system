# Real State System

Este repositorio contiene el sistema inmobiliario completo, estructurado como un monorepo con backend, frontend y documentación funcional.

## Estructura del Proyecto

```
real-state-system/
├── api/        → Backend en Laravel (gestión de contratos, cobranzas, etc.)
├── front/      → Frontend en Vue 3 + Vuetify (gestión administrativa y formularios)
├── docs/       → Documentación funcional (ajustes, servicios, contratos, arquitectura)
├── shared/     → (Opcional) Enums o lógica compartida
├── .gitignore  → Ignora vendor, node_modules, .env, etc.
├── README.md   → Este archivo
└── real-state-system.code-workspace → Workspace VS Code listo para usar
```

## Requisitos

### Backend (Laravel)
- PHP 8.2+
- Composer
- MySQL o PostgreSQL
- Extensiones típicas de Laravel (`pdo`, `mbstring`, `openssl`, etc.)

### Frontend (Vue)
- Node.js 18+
- npm o yarn

## Uso recomendado

1. Clonar el proyecto:
```bash
git clone https://github.com/tu-usuario/real-state-system.git
```

2. Entrar al directorio:
```bash
cd real-state-system
```

3. Abrir en VS Code con workspace:
```bash
code real-state-system.code-workspace
```

4. Levantar cada entorno desde terminales separadas:
```bash
cd api && cp .env.example .env && php artisan key:generate && php artisan serve
cd front && cp .env.example .env && npm install && npm run dev
```

## Documentación

Ver carpeta [`docs/`](./docs) para detalles funcionales del sistema:
- Ajustes de contrato
- Generación de cobranzas
- Servicios
- Estructura de entidades
- Flujo de usuarios
