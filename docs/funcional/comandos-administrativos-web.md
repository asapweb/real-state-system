# Comandos Administrativos - Interfaz Web

## 🎯 **Objetivo**

Permitir que usuarios con permisos administrativos ejecuten comandos de sincronización de índices y asignación de valores desde la interfaz web, sin necesidad de acceder al servidor.

---

## ✅ **Funcionalidades Implementadas**

### **1. Sincronización de Índices**
- ✅ **ICL**: Importar desde BCRA
- ✅ **ICP**: Importar desde INDEC  
- ✅ **UVA**: Importar desde BCRA
- ✅ **Opciones**: Dry run, force, archivo local
- ✅ **Logs en tiempo real**: Ver resultados inmediatamente

### **2. Asignación de Valores**
- ✅ **Comando único**: `adjustments:assign-index-values`
- ✅ **Procesamiento automático**: Asigna valores a ajustes pendientes
- ✅ **Logs detallados**: Información completa del proceso

### **3. Visualización de Logs**
- ✅ **Logs recientes**: Últimos 50 logs por defecto
- ✅ **Filtrado inteligente**: Solo logs relacionados con comandos
- ✅ **Actualización manual**: Botón de refresh

---

## 🔐 **Seguridad y Permisos**

### **1. Rutas Protegidas**
```javascript
// Requiere autenticación y rol administrador
meta: { requiresAuth: true, roles: ['administrador'] }
```

### **2. Validación de Permisos**
- ✅ **Middleware de autenticación**: `auth:sanctum`
- ✅ **Verificación de roles**: Solo administradores
- ✅ **Logs de auditoría**: Registro de usuario que ejecuta comandos

### **3. Validación de Entrada**
```php
$request->validate([
    'index_type' => 'required|in:ICL,ICP,UVA',
    'options' => 'array',
    'options.dry_run' => 'boolean',
    'options.force' => 'boolean',
    'options.file' => 'nullable|string',
]);
```

---

## 🖥️ **Interfaz de Usuario**

### **1. Vista Principal**
- **URL**: `/admin/commands`
- **Acceso**: Solo usuarios con rol `administrador`
- **Layout**: Cards organizadas por funcionalidad

### **2. Sección de Sincronización**
```
┌─────────────────────────────────────┐
│ 🔄 Sincronización de Índices       │
│                                     │
│ Tipo de Índice: [ICL ▼]            │
│ Archivo Local: [___________]        │
│ ☐ Modo Dry Run                     │
│ ☐ Forzar Importación               │
│                                     │
│ [🔄 Sincronizar Índice]            │
└─────────────────────────────────────┘
```

### **3. Sección de Asignación**
```
┌─────────────────────────────────────┐
│ 🧮 Asignar Valores de Índice       │
│                                     │
│ Asignar automáticamente valores    │
│ de índice a ajustes pendientes.    │
│                                     │
│ [🧮 Asignar Valores]               │
└─────────────────────────────────────┘
```

### **4. Sección de Logs**
```
┌─────────────────────────────────────┐
│ 📄 Logs Recientes        [🔄]      │
│                                     │
│ [Área de texto con logs]           │
│                                     │
└─────────────────────────────────────┘
```

---

## 🔧 **API Endpoints**

### **1. Obtener Comandos Disponibles**
```http
GET /api/admin/commands/available
```

**Respuesta:**
```json
{
  "success": true,
  "commands": {
    "sync_indices": {
      "name": "Sincronización de Índices",
      "description": "Importar valores de índices oficiales...",
      "available_types": {
        "ICL": {
          "name": "Índice de Contratos de Locación",
          "source": "BCRA",
          "command": "indices:import-icl-excel"
        }
      }
    }
  }
}
```

### **2. Sincronizar Índices**
```http
POST /api/admin/commands/sync-indices
```

**Body:**
```json
{
  "index_type": "ICL",
  "options": {
    "dry_run": false,
    "force": false,
    "file": "/path/to/file.xlsx"
  }
}
```

### **3. Asignar Valores**
```http
POST /api/admin/commands/assign-index-values
```

### **4. Obtener Logs**
```http
GET /api/admin/commands/logs?lines=50
```

---

## 📊 **Logs y Auditoría**

### **1. Logs de Ejecución**
```php
Log::info('🖥️ Comando ejecutado desde interfaz web', [
    'command' => $command,
    'options' => $commandOptions,
    'user_id' => auth()->id(),
]);
```

### **2. Logs de Resultado**
```php
Log::info('✅ Comando ejecutado exitosamente', [
    'command' => $command,
    'output_length' => strlen($output),
]);
```

### **3. Logs de Error**
```php
Log::error('❌ Error ejecutando comando', [
    'command' => $command,
    'exit_code' => $exitCode,
    'output' => $output,
]);
```

---

## 🧪 **Testing**

### **1. Verificación de Permisos**
```bash
# Usuario sin permisos
curl -H "Authorization: Bearer token" \
     http://localhost/api/admin/commands/available
# Debe retornar 403 Forbidden
```

### **2. Ejecución de Comandos**
```bash
# Sincronizar ICL en modo dry-run
curl -X POST \
     -H "Authorization: Bearer token" \
     -H "Content-Type: application/json" \
     -d '{"index_type":"ICL","options":{"dry_run":true}}' \
     http://localhost/api/admin/commands/sync-indices
```

### **3. Verificación de Logs**
```bash
# Obtener logs recientes
curl -H "Authorization: Bearer token" \
     http://localhost/api/admin/commands/logs?lines=10
```

---

## 📋 **Archivos Implementados**

### **Backend:**
- ✅ `api/app/Http/Controllers/AdminCommandsController.php`
- ✅ `api/routes/api.php` (rutas agregadas)

### **Frontend:**
- ✅ `front/src/views/admin/AdminCommands.vue`
- ✅ `front/src/router/index.js` (ruta agregada)

### **Documentación:**
- ✅ `docs/funcional/comandos-administrativos-web.md`

---

## 🎯 **Casos de Uso**

### **Caso 1: Sincronización Mensual**
1. **Administrador** accede a `/admin/commands`
2. **Selecciona** ICL en el formulario
3. **Marca** "Modo Dry Run" para verificar
4. **Ejecuta** el comando
5. **Revisa** logs para confirmar resultados
6. **Ejecuta** sin dry run para importar datos

### **Caso 2: Asignación de Valores**
1. **Administrador** accede a `/admin/commands`
2. **Hace click** en "Asignar Valores"
3. **Espera** procesamiento (puede tomar tiempo)
4. **Revisa** logs para ver resultados
5. **Verifica** en ajustes que se asignaron valores

### **Caso 3: Debugging**
1. **Administrador** accede a `/admin/commands`
2. **Revisa** logs recientes
3. **Identifica** errores o problemas
4. **Ejecuta** comandos con opciones específicas
5. **Monitorea** resultados en tiempo real

---

## 🚀 **Beneficios**

### **1. Facilidad de Uso**
- ✅ **Sin terminal**: No requiere acceso SSH
- ✅ **Interfaz intuitiva**: Formularios claros
- ✅ **Feedback inmediato**: Logs en tiempo real

### **2. Seguridad**
- ✅ **Permisos granulares**: Solo administradores
- ✅ **Auditoría completa**: Logs de todas las acciones
- ✅ **Validación robusta**: Entrada validada

### **3. Mantenimiento**
- ✅ **Monitoreo fácil**: Logs centralizados
- ✅ **Debugging rápido**: Información detallada
- ✅ **Escalabilidad**: Fácil agregar nuevos comandos

---

## 🔮 **Futuras Mejoras**

### **1. Programación Automática**
- ✅ **Cron jobs web**: Programar comandos desde la UI
- ✅ **Notificaciones**: Alertas por email/SMS
- ✅ **Dashboard**: Métricas de ejecución

### **2. Comandos Adicionales**
- ✅ **Generación de datos**: Crear datos de prueba
- ✅ **Backup/restore**: Gestión de datos
- ✅ **Mantenimiento**: Limpieza de logs

### **3. Monitoreo Avanzado**
- ✅ **Gráficos**: Visualización de resultados
- ✅ **Alertas**: Detección de errores
- ✅ **Reportes**: Exportación de logs

**¡La interfaz web para comandos administrativos está implementada y funcionando!** 🚀

Ahora los administradores pueden ejecutar comandos de sincronización y asignación de valores de índice directamente desde la interfaz web, con logs detallados y auditoría completa. 