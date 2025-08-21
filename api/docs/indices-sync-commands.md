# Comandos de Sincronización de Índices

Este documento describe todos los comandos disponibles para sincronizar índices oficiales desde fuentes externas.

## Comandos de Configuración

### 0. Crear Tipos de Índice

```bash
# Crear tipo de índice ICP
php artisan indices:create-icp-type

# Crear tipo de índice UVA
php artisan indices:create-uva-type
```

Estos comandos crean los tipos de índice ICP y UVA si no existen en la base de datos. Es necesario ejecutarlos antes de importar datos.

## Comandos Principales

### 1. Importar ICL (Índice de Contratos de Locación)

```bash
php artisan indices:import-icl-excel [opciones]
```

**Opciones:**
- `--file=` - Ruta al archivo Excel local
- `--force` - Forzar importación incluso si ya existe el valor
- `--dry-run` - Solo mostrar qué se importaría sin guardar

**Ejemplos:**
```bash
# Importar desde BCRA automáticamente
php artisan indices:import-icl-excel

# Importar desde archivo local
php artisan indices:import-icl-excel --file=/path/to/icl.xlsx

# Modo dry-run para ver qué se importaría
php artisan indices:import-icl-excel --dry-run

# Forzar importación completa
php artisan indices:import-icl-excel --force
```

### 2. Importar ICP (Índice de Casa Propia)

```bash
php artisan indices:import-icp-excel [opciones]
```

**Opciones:**
- `--file=` - Ruta al archivo Excel local
- `--force` - Forzar importación incluso si ya existe el valor
- `--dry-run` - Solo mostrar qué se importaría sin guardar

**Ejemplos:**
```bash
# Importar desde INDEC automáticamente
php artisan indices:import-icp-excel

# Importar desde archivo local
php artisan indices:import-icp-excel --file=/path/to/icp.xlsx

# Modo dry-run para ver qué se importaría
php artisan indices:import-icp-excel --dry-run

# Forzar importación completa
php artisan indices:import-icp-excel --force
```

### 3. Importar UVA (Unidad de Valor Adquisitivo)

```bash
php artisan indices:import-uva-excel [opciones]
```

**Opciones:**
- `--file=` - Ruta al archivo Excel local
- `--force` - Forzar importación incluso si ya existe el valor
- `--dry-run` - Solo mostrar qué se importaría sin guardar

**Ejemplos:**
```bash
# Importar desde BCRA automáticamente
php artisan indices:import-uva-excel

# Importar desde archivo local
php artisan indices:import-uva-excel --file=/path/to/uva.xlsx

# Modo dry-run para ver qué se importaría
php artisan indices:import-uva-excel --dry-run

# Forzar importación completa
php artisan indices:import-uva-excel --force
```

## Comandos de Diagnóstico

### 4. Probar Conexión ICP

```bash
php artisan indices:test-icp-connection
```

Este comando prueba la conexión y descarga del archivo ICP desde INDEC, mostrando:
- Estado de la conexión HTTP
- Tamaño del archivo descargado
- Headers de respuesta
- Resultado del parseo del archivo
- Muestra de datos extraídos

### 5. Buscar URLs Alternativas ICP

```bash
php artisan indices:search-icp-urls
```

Este comando busca URLs alternativas para el archivo ICP del INDEC, probando múltiples variaciones de la URL y sugiriendo la mejor opción disponible.

### 6. Listar Fuentes Disponibles

```bash
php artisan indices:list-sources
```

Muestra todas las fuentes de índices disponibles con sus URLs y descripciones.

## Comandos de Generación de Datos de Prueba

### 7. Generar Datos de Prueba ICP

```bash
php artisan indices:generate-test-icp-data [opciones]
```

**Opciones:**
- `--months=24` - Número de meses de datos a generar (por defecto: 24)
- `--start-date=` - Fecha de inicio (YYYY-MM-DD)

**Ejemplos:**
```bash
# Generar 24 meses de datos de prueba
php artisan indices:generate-test-icp-data

# Generar 12 meses desde una fecha específica
php artisan indices:generate-test-icp-data --months=12 --start-date=2024-01-01
```

### 8. Generar Datos de Prueba UVA

```bash
php artisan indices:generate-test-uva-data [opciones]
```

**Opciones:**
- `--months=24` - Número de meses de datos a generar (por defecto: 24)
- `--start-date=` - Fecha de inicio (YYYY-MM-DD)

**Ejemplos:**
```bash
# Generar 24 meses de datos de prueba
php artisan indices:generate-test-uva-data

# Generar 12 meses desde una fecha específica
php artisan indices:generate-test-uva-data --months=12 --start-date=2024-01-01
```

## Flujo de Trabajo Recomendado

### Para ICP (Primera vez):

1. **Crear tipo de índice:**
   ```bash
   php artisan indices:create-icp-type
   ```

2. **Probar conexión:**
   ```bash
   php artisan indices:test-icp-connection
   ```

3. **Importar datos:**
   ```bash
   php artisan indices:import-icp-excel --dry-run
   php artisan indices:import-icp-excel
   ```

### Para ICL:

1. **Importar datos directamente:**
   ```bash
   php artisan indices:import-icl-excel --dry-run
   php artisan indices:import-icl-excel
   ```

## Fuentes de Datos

### BCRA (Banco Central de la República Argentina)
- **URL:** https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_icl.xls
- **Índice:** ICL (Índice de Contratos de Locación)
- **Formato:** Excel (.xls)
- **Frecuencia:** Mensual

### INDEC (Instituto Nacional de Estadística y Censos)
- **URL:** https://www.indec.gob.ar/ftp/cuadros/economia/icp.xls
- **Índice:** ICP (Índice de Casa Propia)
- **Formato:** Excel (.xls)
- **Frecuencia:** Mensual

## Tipos de Índices Soportados

### ICL (Índice de Contratos de Locación)
- **Código:** `ICL`
- **Modo de cálculo:** `ratio`
- **Fuente:** BCRA
- **Descripción:** Índice que mide la evolución de los Contratos de Locación

### UVA (Unidad de Valor Adquisitivo)
- **Código:** `UVA`
- **Modo de cálculo:** `ratio`
- **Fuente:** BCRA
- **Descripción:** Unidad de valor utilizada para créditos hipotecarios y ajustes

## Modos de Cálculo

### Ratio
- Los valores se almacenan como ratios directos
- Se calcula el promedio entre fechas para ajustes
- Fórmula de aplicación: `monto * value`

### Percentage
- Los valores se almacenan como porcentajes
- Se usa directamente el valor del mes
- Fórmula de aplicación: `monto * (1 + value / 100)`

## Comando de Asignación Automática

### Asignar Valores a Ajustes Pendientes

```bash
php artisan adjustments:assign-index-values
```

Este comando asigna automáticamente valores de índice a los ajustes de contrato pendientes, considerando:
- El modo de cálculo del índice (ratio vs percentage)
- La fecha efectiva del ajuste
- La fecha de inicio del contrato (para modo ratio)

## Programación Automática

Los comandos pueden programarse en el Kernel de Laravel:

```php
// En app/Console/Kernel.php
$schedule->command('indices:import-icl-excel')->monthlyOn(1, '03:00');
$schedule->command('indices:import-icp-excel')->monthlyOn(1, '03:30');
$schedule->command('indices:import-uva-excel')->monthlyOn(1, '04:00');
$schedule->command('adjustments:assign-index-values')->monthlyOn(1, '04:30');
```

## Logs y Debugging

Todos los comandos generan logs detallados en:
- `storage/logs/laravel.log`
- Archivos de debug en `storage/app/`

Para debugging manual, los archivos descargados se guardan en:
- `storage/app/icl_bcra_download.xlsx`
- `storage/app/icp_indec_test.xlsx`

## Manejo de Errores

### Problemas Comunes

1. **Error SSL:** Los comandos intentan descarga segura primero, luego fallback sin verificación SSL
2. **Archivo no encontrado:** Verificar que la URL del archivo esté actualizada
3. **Formato de archivo:** Los archivos deben ser Excel (.xls o .xlsx)
4. **Datos faltantes:** Algunos archivos pueden tener formatos inconsistentes
5. **Tipo de índice no existe:** Ejecutar `indices:create-icp-type` para crear el tipo ICP

### Soluciones

- Usar `--file=` para archivos locales cuando la descarga falla
- Usar `--dry-run` para verificar qué datos se extraerían
- Revisar logs para detalles de errores específicos
- Verificar conectividad de red en el contenedor
- Ejecutar comandos de configuración antes de importar 
