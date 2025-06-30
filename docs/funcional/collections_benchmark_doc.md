## ⚙️ Herramienta de Benchmark de Cobranzas

Este módulo incluye un comando técnico destinado a medir el **rendimiento real de generación masiva de cobranzas mensuales**.

### 💾 Comando Artisan

```bash
php artisan collections:benchmark --month=YYYY-MM
```

### 🔍 ¿Qué hace?

- Ejecuta `CollectionGenerationService::generateForMonth()` para el mes indicado.
- Mide el tiempo total de ejecución.
- Informa la cantidad total de cobranzas generadas.
- Muestra un **desglose por moneda**, incluyendo total de cobranzas y monto total acumulado por moneda.

### 📅 Datos de prueba

Para simular un escenario realista de carga, se incluye un seeder:

```bash
php artisan db:seed --class=ContractBenchmarkSeeder
```

Este seeder genera:

- 500 contratos activos con vigencia desde julio 2025
- 1 cliente y 1 propiedad por contrato
- Alquiler mensual en ARS + un gasto en USD
- Resultado esperado: 500 cobranzas en ARS y 500 en USD (total 1.000)

### 📌 Ejemplo de ejecución

```bash
php artisan collections:benchmark --month=2025-07
```

```
Generando cobranzas para July 2025...
✔ 1000 cobranzas generadas en 33.199 segundos.

Desglose por moneda:
- ARS: 500 cobranzas · Total: 26.500.000,00 ARS
- USD: 500 cobranzas · Total: 50.000,00 USD
```

### 💡 Uso recomendado

- Validar performance en entornos de desarrollo
- Comparar mejoras de rendimiento tras refactorizaciones
- Estimar tiempos para procesos batch en producción

