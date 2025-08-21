<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Gestión de Tipos de Índice</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog(null)">
        Crear Índice
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Administra los tipos de índice disponibles para los ajustes de contratos.
    </p>
  </div>
  <v-row>
    <v-col cols="12" class="mb-4">
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:sort-by="options.sortBy"
        v-model:page="options.page"
        :headers="headers"
        :items="indexTypes"
        :items-length="total"
        :loading="loading"
        item-value="id"
        @update:options="fetchIndexTypes"
      >
        <template #[`item.code`]="{ item }">
          {{ item.code }}
        </template>

        <template #[`item.name`]="{ item }">
          {{ item.name }}
        </template>

        <template #[`item.is_active`]="{ item }">
          <v-chip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
            variant="flat"
          >
            {{ item.is_active ? 'Activo' : 'Inactivo' }}
          </v-chip>
        </template>

        <template #[`item.calculation_mode`]="{ item }">
          <span class="text">
            {{ getCalculationModeLabel(item.calculation_mode) }}
          </span>
          <span class="text-medium-emphasis ms-2" v-if="item.is_cumulative">
            (Acumulado)
          </span>
        </template>

        <template #[`item.frequency`]="{ item }">
          {{ getFrequencyLabel(item.frequency) }}
        </template>

        <template #[`item.actions`]="{ item }">
            <v-icon
              class="me-2"
              size="small"
              @click="openDialog(item)"
              title="Editar tipo de índice"
            >
              mdi-pencil
            </v-icon>
            <v-icon
              size="small"
              @click="confirmDelete(item)"
              title="Eliminar tipo de índice"
            >
            mdi-delete
          </v-icon>
        </template>
      </v-data-table-server>
    </v-col>
  </v-row>
  <!-- Formulario embebido -->
  <v-dialog v-model="dialog" max-width="780px">
    <IndexTypeForm
      :initial-data="selectedIndexType"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Confirmación de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que querés eliminar el tipo de índice "{{ indexTypeToDelete?.name }}"?
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteIndexType">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '@/services/axios'
import IndexTypeForm from './components/IndexTypeForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const snackbar = useSnackbar()

const dialog = ref(false)
const dialogDelete = ref(false)
const selectedIndexType = ref(null)
const indexTypeToDelete = ref(null)
const indexTypes = ref([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }],
})

const filters = reactive({
  search: {
    name: '',
    code: '',
  },
})

const headers = [
  { title: 'Código', key: 'code', sortable: true },
  { title: 'Nombre', key: 'name', sortable: true },
  { title: 'Frecuencia', key: 'frequency', sortable: true },
  { title: 'Cálculo', key: 'calculation_mode', sortable: true },
  { title: 'Estado', key: 'is_active', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const fetchIndexTypes = async () => {
  loading.value = true
  try {
    const params = {
      page: options.page,
      per_page: options.itemsPerPage,
      sort_by: options.sortBy[0]?.key,
      sort_direction: options.sortBy[0]?.order,
      ...filters,
    }

    const { data } = await axios.get('/api/index-types', { params })
    indexTypes.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar tipos de índice')
  } finally {
    loading.value = false
  }
}

const openDialog = (item) => {
  selectedIndexType.value = item
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedIndexType.value = null
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (formData.id) {
      await axios.put(`/api/index-types/${formData.id}`, formData)
    } else {
      await axios.post('/api/index-types', formData)
    }
    snackbar.success('Tipo de índice guardado correctamente')
    fetchIndexTypes()
    dialog.value = false
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al guardar tipo de índice'
    snackbar.error(errorMessage)
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  indexTypeToDelete.value = item
  dialogDelete.value = true
}

const deleteIndexType = async () => {
  try {
    await axios.delete(`/api/index-types/${indexTypeToDelete.value.id}`)
    snackbar.success('Tipo de índice eliminado')
    fetchIndexTypes()
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al eliminar tipo de índice'
    snackbar.error(errorMessage)
  } finally {
    dialogDelete.value = false
    indexTypeToDelete.value = null
  }
}

// Funciones helper para CalculationMode
const getCalculationModeLabel = (mode) => {
  const labels = {
    'percentage': 'Porcentaje',
    'ratio': 'Ratio',
    'multiplicative_chain': 'Cadena Multiplicativa',
  }
  return labels[mode] || mode
}

// Funciones helper para Frequency
const getFrequencyLabel = (frequency) => {
  const labels = {
    'daily': 'Diaria',
    'monthly': 'Mensual',
  }
  return labels[frequency] || frequency
}

onMounted(() => {
  fetchIndexTypes()
})
</script>
