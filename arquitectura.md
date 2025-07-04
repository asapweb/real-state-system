# Arquitectura del Sistema

## Estructura General

El sistema inmobiliario está compuesto por:

- **Backend**: Laravel 11 (`api/`)
- **Frontend**: Vue 3 + Vuetify (`front/`)
- **Documentación funcional**: Markdown (`docs/`)
- **Workspace VS Code**: Proyecto preconfigurado (`real-state-system.code-workspace`)
- **Control de versiones**: Repositorio unificado (monorepo)

## Hito: Consolidación en Monorepo

Fecha: Junio 2025

Se consolidaron los proyectos `real-state-api` y `real-state-front` en un único repositorio `real-state-system`, con los siguientes objetivos:

- Facilitar navegación cruzada con Codex (ChatGPT)
- Unificar documentación funcional con el código
- Centralizar estructura de desarrollo y validaciones
- Optimizar onboarding y colaboración

Este repositorio no reemplaza los repos individuales en producción, pero sirve como base para documentación, revisión de lógica, y análisis funcional.

## Próximos pasos

- Crear el repositorio `real-state-system` en GitHub (privado)
- Vincularlo con Codex para análisis completo
- Mantener sincronización manual con los repos originales hasta que se establezca un flujo automatizado
