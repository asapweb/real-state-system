<template>
  <v-container>
    <v-card>
      <v-card-title class="text-h5">
        <v-icon class="me-2">mdi-cog</v-icon>
        Comandos Administrativos
      </v-card-title>

      <v-card-text>
        <v-alert
          v-if="!hasAdminPermissions"
          type="warning"
          variant="tonal"
          class="mb-4"
        >
          <v-icon class="me-2">mdi-alert</v-icon>
          No tienes permisos para ejecutar comandos administrativos.
        </v-alert>

        <div v-else>
          <!-- Sincronización de Índices -->
          <v-card variant="outlined" class="mb-4">
            <v-card-title class="text-h6">
              <v-icon class="me-2">mdi-sync</v-icon>
              Sincronización de Índices
            </v-card-title>

            <v-card-text>
              <p class="text-body-2 mb-4">
                Importar valores de índices oficiales desde fuentes externas (BCRA, INDEC).
              </p>

              <v-form @submit.prevent="syncIndices">
                <v-row>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="syncForm.index_type"
                      :items="availableIndexTypes"
                      item-title="name"
                      item-value="code"
                      label="Tipo de Índice"
                      required
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="syncForm.options.file"
                      label="Archivo Local (opcional)"
                      placeholder="/path/to/file.xlsx"
                      hint="Ruta al archivo Excel local"
                      persistent-hint
                    />
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-checkbox
                      v-model="syncForm.options.dry_run"
                      label="Modo Dry Run"
                      hint="Solo mostrar qué se importaría sin guardar"
                      persistent-hint
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12" md="4">
                    <v-checkbox
                      v-model="syncForm.options.force"
                      label="Forzar Importación"
                      hint="Importar incluso si ya existe el valor"
                      persistent-hint
                    />
                  </v-col>

                  <v-col cols="12" md="8">
                    <v-btn
                      type="submit"
                      color="primary"
                      :loading="syncLoading"
                      :disabled="!syncForm.index_type"
                    >
                      <v-icon class="me-2">mdi-sync</v-icon>
                      Sincronizar Índice
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Asignación de Valores -->
          <v-card variant="outlined" class="mb-4">
            <v-card-title class="text-h6">
              <v-icon class="me-2">mdi-calculator</v-icon>
              Asignar Valores de Índice
            </v-card-title>

            <v-card-text>
              <p class="text-body-2 mb-4">
                Asignar automáticamente valores de índice a ajustes de contrato pendientes.
              </p>

              <v-btn
                color="success"
                :loading="assignLoading"
                @click="assignIndexValues"
              >
                <v-icon class="me-2">mdi-calculator</v-icon>
                Asignar Valores
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- Logs Recientes -->
          <v-card variant="outlined">
            <v-card-title class="text-h6">
              <v-icon class="me-2">mdi-text-box</v-icon>
              Logs Recientes
              <v-spacer />
              <v-btn
                icon
                size="small"
                @click="loadLogs"
                :loading="logsLoading"
              >
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>

            <v-card-text>
              <v-textarea
                v-model="logs"
                readonly
                rows="10"
                variant="outlined"
                label="Logs de Comandos"
                class="font-family-monospace"
              />
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>
    </v-card>

    <!-- Snackbar para notificaciones -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
    >
      {{ snackbar.message }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '@/services/axios'

// Estado del componente
const hasAdminPermissions = ref(true) // TODO: Verificar permisos reales
const syncLoading = ref(false)
const assignLoading = ref(false)
const logsLoading = ref(false)
const logs = ref('')

// Formulario de sincronización
const syncForm = reactive({
  index_type: '',
  options: {
    dry_run: false,
    force: false,
    file: '',
  },
})

// Tipos de índice disponibles
const availableIndexTypes = ref([
  { code: 'ICL', name: 'ICL - Índice de Contratos de Locación (BCRA)' },
])

// Snackbar
const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
  timeout: 5000,
})

// Métodos
const showSnackbar = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

const syncIndices = async () => {
  if (!syncForm.index_type) {
    showSnackbar('Selecciona un tipo de índice', 'warning')
    return
  }

  syncLoading.value = true

  try {
    const response = await axios.post('/api/admin/commands/sync-indices', {
      index_type: syncForm.index_type,
      options: syncForm.options,
    })

    if (response.data.success) {
      showSnackbar(response.data.message, 'success')
      loadLogs() // Recargar logs
    } else {
      showSnackbar(response.data.message, 'error')
    }
  } catch (error) {
    console.error('Error sincronizando índices:', error)
    const message = error.response?.data?.message || 'Error ejecutando comando'
    showSnackbar(message, 'error')
  } finally {
    syncLoading.value = false
  }
}

const assignIndexValues = async () => {
  assignLoading.value = true

  try {
    const response = await axios.post('/api/admin/commands/assign-index-values')

    if (response.data.success) {
      showSnackbar(response.data.message, 'success')
      loadLogs() // Recargar logs
    } else {
      showSnackbar(response.data.message, 'error')
    }
  } catch (error) {
    console.error('Error asignando valores:', error)
    const message = error.response?.data?.message || 'Error ejecutando comando'
    showSnackbar(message, 'error')
  } finally {
    assignLoading.value = false
  }
}

const loadLogs = async () => {
  logsLoading.value = true

  try {
    const response = await axios.get('/api/admin/commands/logs', {
      params: { lines: 50 }
    })

    if (response.data.success) {
      logs.value = response.data.logs.join('\n')
    } else {
      logs.value = 'Error cargando logs'
    }
  } catch (error) {
    console.error('Error cargando logs:', error)
    logs.value = 'Error cargando logs'
  } finally {
    logsLoading.value = false
  }
}

// Cargar logs al montar el componente
onMounted(() => {
  loadLogs()
})
</script>

<style scoped>
.font-family-monospace {
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}
</style>
