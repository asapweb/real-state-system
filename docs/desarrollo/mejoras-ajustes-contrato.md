# Mejoras Técnicas - Sistema de Ajustes de Contrato

Este documento describe las mejoras técnicas implementadas en el sistema de ajustes de contrato, incluyendo el soporte para múltiples índices oficiales y modos de cálculo.

---

## 1. Nuevas Funcionalidades Implementadas

### 1.1. Soporte Multi-Índice
- **ICL (Índice de Contratos de Locación)**: BCRA
- **ICP (Índice de Casa Propia)**: INDEC  
- **UVA (Unidad de Valor Adquisitivo)**: BCRA
- **Sistema extensible**: Fácil agregar nuevos índices

### 1.2. Modos de Cálculo
- **Ratio Mode**: Para índices que representan coeficientes directos
- **Percentage Mode**: Para índices que representan porcentajes de variación

### 1.3. Importación Automática
- Comandos Artisan para importar desde Excel oficial
- Manejo de errores SSL y fallbacks
- Validación de datos y logging detallado

---

## 2. Arquitectura del Sistema

### 2.1. Modelos Principales

#### `IndexType`
```php
- id
- code (ICL, ICP, UVA, etc.)
- name
- is_active
- calculation_mode (ratio/percentage)
```

#### `IndexValue`
```php
- index_type_id
- date (para ratio mode)
- period (para percentage mode)
- value (para ratio mode)
- percentage (para percentage mode)
```

#### `ContractAdjustment`
```php
- contract_id
- type (index, percentage, fixed, negotiated)
- index_type_id (para type = index)
- effective_date
- value (calculado automáticamente para índices)
- applied_amount
- applied_at
```

### 2.2. Servicios Implementados

#### `ICLExcelImportService`
- Importación desde Excel oficial
- Parsers específicos por fuente (BCRA, INDEC)
- Manejo de errores y logging
- Soporte para dry-run

#### `ContractAdjustmentService`
- Aplicación de ajustes con validaciones
- Cálculo según modo (ratio/percentage)
- Asignación automática de valores de índice

#### `AssignIndexValuesToAdjustmentsService`
- Proceso automático mensual
- Asignación de valores a ajustes pendientes

---

## 3. Comandos Artisan Implementados

### 3.1. Importación de Índices
```bash
# ICL
php artisan indices:import-icl-excel [--dry-run] [--force] [--file=path]

# ICP  
php artisan indices:import-icp-excel [--dry-run] [--force] [--file=path]

# UVA
php artisan indices:import-uva-excel [--dry-run] [--force] [--file=path]
```

### 3.2. Generación de Datos de Prueba
```bash
php artisan indices:generate-test-icl-data --months=24
php artisan indices:generate-test-icp-data --months=24  
php artisan indices:generate-test-uva-data --months=24
```

### 3.3. Gestión de Tipos de Índice
```bash
php artisan indices:create-icl-type
php artisan indices:create-icp-type
php artisan indices:create-uva-type
```

### 3.4. Asignación Automática
```bash
php artisan adjustments:assign-index-values
```

---

## 4. Lógica de Cálculo

### 4.1. Ratio Mode (ICL, ICP, UVA)
```php
// Cálculo del valor del ajuste
$startDate = $contract->start_date;
$effectiveDate = $adjustment->effective_date;

// Obtener valores entre start_date y effective_date
$indexValues = IndexValue::where('index_type_id', $adjustment->index_type_id)
    ->where('date', '>=', $startDate)
    ->where('date', '<=', $effectiveDate)
    ->get();

// Calcular promedio y variación
$averageValue = $indexValues->avg('value');
$baseValue = $indexValues->first()->value;
$percentageChange = (($averageValue - $baseValue) / $baseValue) * 100;

// Aplicar al alquiler
$newRent = $contract->monthly_amount * (1 + $percentageChange / 100);
```

### 4.2. Percentage Mode
```php
// Tomar valor directo del índice
$indexValue = IndexValue::where('index_type_id', $adjustment->index_type_id)
    ->where('period', $adjustment->effective_date->format('Y-m'))
    ->first();

$adjustment->value = $indexValue->percentage;

// Aplicar al alquiler
$newRent = $contract->monthly_amount * (1 + $adjustment->value / 100);
```

---

## 5. Validaciones Implementadas

### 5.1. Al Aplicar Ajustes
- ✅ Verificar que no esté ya aplicado
- ✅ Verificar que tenga valor definido
- ✅ Verificar que no exista cobranza emitida para el período
- ✅ Verificar que no exista otro ajuste aplicado para el mismo período

### 5.2. Al Importar Índices
- ✅ Validar formato del archivo Excel
- ✅ Verificar encabezados correctos
- ✅ Validar datos numéricos
- ✅ Manejar errores de descarga

### 5.3. Al Asignar Valores
- ✅ Verificar que el ajuste sea de tipo index
- ✅ Verificar que no tenga valor ya asignado
- ✅ Verificar que no esté ya aplicado
- ✅ Calcular según modo de cálculo

---

## 6. Manejo de Errores

### 6.1. Problemas de Descarga
- Fallback a descarga sin verificación SSL
- Logging detallado de errores
- Opción de usar archivo local

### 6.2. Problemas de Parsing
- Parsers específicos por fuente
- Búsqueda de encabezados en múltiples filas
- Logging de debugging detallado

### 6.3. Problemas de Datos
- Validación de valores numéricos
- Manejo de fechas en diferentes formatos
- Filtrado de datos inválidos

---

## 7. Logging y Monitoreo

### 7.1. Logs Implementados
```php
// Importación
Log::info('Iniciando importación ICL desde Excel');
Log::info('Datos extraídos del Excel', ['count' => count($data)]);
Log::error('Error parseando archivo Excel', ['error' => $e->getMessage()]);

// Aplicación de ajustes
Log::info('Aplicando ajuste', ['adjustment_id' => $adjustment->id]);
Log::error('Error aplicando ajuste', ['error' => $e->getMessage()]);
```

### 7.2. Métricas de Monitoreo
- Cantidad de registros importados
- Errores de parsing
- Tiempo de procesamiento
- Ajustes aplicados exitosamente

---

## 8. Configuración y Deployment

### 8.1. Cron Jobs
```php
// En app/Console/Kernel.php
$schedule->command('adjustments:assign-index-values')->monthlyOn(1, '02:00');
```

### 8.2. Variables de Entorno
```env
# Configuración de timeouts para descargas
CURL_TIMEOUT=60
EXCEL_IMPORT_MAX_ROWS=10000
```

### 8.3. Permisos de Archivos
```bash
# Para descargas temporales
chmod 755 storage/app/temp/
```

---

## 9. Testing

### 9.1. Tests Implementados
- `ContractAdjustmentServiceTest`: Pruebas de aplicación de ajustes
- `CollectionGenerationMulticurrencyTest`: Pruebas con múltiples monedas
- Tests de importación de índices

### 9.2. Datos de Prueba
- Comandos para generar datos de prueba
- Factories para modelos
- Seeders para tipos de índice

---

## 10. Roadmap Futuro

### 10.1. Mejoras Planificadas
- Dashboard de seguimiento de índices
- Notificaciones automáticas
- Simulación de ajustes
- API para consulta de índices

### 10.2. Nuevos Índices
- RIPTE (Índice de Precios al Consumidor)
- CER (Coeficiente de Estabilización de Referencia)
- Índices personalizados por región

### 10.3. Optimizaciones
- Cache de valores de índice
- Procesamiento en background
- Validaciones más robustas 