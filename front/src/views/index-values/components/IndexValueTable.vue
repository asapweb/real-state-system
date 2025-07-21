<template>
  <div class="d-flex align-center mb-5 gap-2">
    <v-select
      v-model="filters.index_type_id"
      :items="indexTypeOptions"
      item-title="name"
      item-value="id"
      label="Tipo de Índice"
      variant="solo-filled"
      flat
      hide-details
      clearable
      @update:model-value="handleFilterChange"
    />
    <v-text-field
      v-model="filters.effective_date_from"
      label="Desde"
      type="date"
      @update:model-value="handleFilterChange"
      variant="solo-filled"
      flat
      hide-details
      clearable
      class="ml-2"
    />
    <v-text-field
      v-model="filters.effective_date_to"
      label="Hasta"
      type="date"
      @update:model-value="handleFilterChange"
      variant="solo-filled"
      flat
      hide-details
      clearable
      class="ml-2"
    />
  </div>

  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="indexValues"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="handleOptionsUpdate"
  >
    <template #[`item.index_type`]="{ item }">
      <v-chip size="small" color="primary" variant="flat">
        {{ item.index_type?.name || '—' }}
      </v-chip>
    </template>

    <template #[`item.calculation_mode`]="{ item }">
      <v-chip
        size="small"
        :color="getCalculationModeColor(item.calculation_mode)"
        variant="flat"
      >
        {{ getCalculationModeLabel(item.calculation_mode) }}
      </v-chip>
    </template>

    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date, item.index_type) }}
    </template>

    <template #[`item.value`]="{ item }">
      <span v-if="item.value" class="font-weight-bold">
        {{ item.calculation_mode === 'percentage' ? item.value + '%' : item.value }}
      </span>
      <span v-else>—</span>
    </template>

    <template #[`item.actions`]="{ item }">
      <div class="d-flex align-center">
        <v-icon
          class="me-2"
          size="small"
          @click="$emit('edit', item)"
          title="Editar valor de índice"
        >
          mdi-pencil
        </v-icon>
        <v-icon
          size="small"
          @click="$emit('delete', item)"
          title="Eliminar valor de índice"
        >
          mdi-delete
        </v-icon>
      </div>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import indexValueService from '@/services/indexValueService'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['create', 'edit', 'delete', 'update:options'])

const indexValues = ref([])
const total = ref(0)
const indexTypeOptions = ref([])

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'desc' }],
})

const filters = reactive({
  index_type_id: '',
  effective_date_from: '',
  effective_date_to: '',
})

const headers = [
  { title: 'Tipo de Índice', key: 'index_type', sortable: false },
  { title: 'Modo de Cálculo', key: 'calculation_mode', sortable: false },
  { title: 'Fecha', key: 'effective_date', sortable: true },
  { title: 'Valor', key: 'value', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const formatDate = (date, indexType = null) => {
  if (!date) return '—'

  // Si es un índice mensual, mostrar MM/YYYY
  if (indexType?.frequency === 'monthly') {
    const dateObj = new Date(date)
    const month = String(dateObj.getMonth() + 1).padStart(2, '0')
    const year = dateObj.getFullYear()
    return `${month}/${year}`
  }

  // Para índices diarios o cuando no se conoce la frecuencia, usar el formato original
  return indexValueService.formatDate(date)
}

const fetchIndexTypes = async () => {
  try {
    const { data } = await indexValueService.getIndexTypes()
    indexTypeOptions.value = data
  } catch (e) {
    console.error('Error cargando tipos de índice:', e)
  }
}

const fetchIndexValues = async () => {
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key,
    sort_direction: options.sortBy[0]?.order,
    ...filters,
  }

  try {
    const { data, meta } = await indexValueService.getIndexValues(params)
    indexValues.value = data.map(item => ({
      ...item,
      calculation_mode: item.calculation_mode || item.index_type?.calculation_mode || null
    }))
    total.value = meta.total
  } catch (e) {
    console.error('Error cargando valores de índice:', e)
  }
}

const handleFilterChange = () => {
  options.page = 1
  fetchIndexValues()
}

const handleOptionsUpdate = (newOptions) => {
  options.page = newOptions.page
  options.itemsPerPage = newOptions.itemsPerPage
  options.sortBy = newOptions.sortBy
  fetchIndexValues()
}

onMounted(() => {
  fetchIndexTypes()
  fetchIndexValues()
  window.addEventListener('refresh-index-values', fetchIndexValues)
})
onUnmounted(() => {
  window.removeEventListener('refresh-index-values', fetchIndexValues)
})

watch(() => [filters.index_type_id, filters.effective_date_from, filters.effective_date_to], () => {
  options.page = 1
  fetchIndexValues()
})

// Funciones helper para CalculationMode
const getCalculationModeLabel = (mode) => {
  const labels = {
    'percentage': 'Porcentaje',
    'ratio': 'Ratio',
    'multiplicative_chain': 'Cadena Multiplicativa',
  }
  return labels[mode] || mode
}

const getCalculationModeColor = (mode) => {
  const colors = {
    'percentage': 'success',
    'ratio': 'info',
    'multiplicative_chain': 'warning',
  }
  return colors[mode] || 'default'
}
</script>
