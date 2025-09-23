<template>
  <div class="d-flex flex-column gap-4">
    <!-- Tabla -->
    <v-data-table-server
      :headers="headers"
      :items="items"
      :items-length="total"
      :loading="loading"
      :items-per-page="options.itemsPerPage"
      :page="options.page"
      :sort-by="options.sortBy"
      :fixed-header="true"
      height="62vh"
      v-model="selected"
      @update:options="onUpdateOptions"
    >
       <!-- ID -->
       <template #item.id="{ item }">
        <div class="d-flex flex-column">
          <span class="font-weight-medium">{{ item.main_tenant.client.full_name}}</span>
          <small class="text-medium-emphasis"><RouterLink :to="`/contracts/${item.id}`" class="text-primary text-decoration-none">
          {{ formatModelId(item.id, 'CON') }}
            <!-- {{ item.contract.id }} -->
          </RouterLink> ({{ formatDate(item.start_date) }})</small>
        </div>
      </template>

      <!-- Valor planificado -->
      <template #item.period_value="{ item }">
        <div class="text-right">
          {{ formatMoney(item.period_value, item.currency) }}
        </div>
      </template>

      <!-- Último ajuste -->
      <template #item.last_planned_adjustment="{ item }">
        {{ formatMoney(item.period_value, item.currency) }}
        <div v-if="item._lastPlanned">
          <small class="text-medium-emphasis">
          Ultimo ajuste ({{ formatPeriodWithMonthName(item._lastPlanned.effective_date) }})
        </small>
        </div>
      </template>

      <!-- Renta -->
      <template #item.rent_info="{ item }">
        <div v-if="item._rent">
          <span class="font-weight-medium">{{ formatMoney(item._rent.amount, item.currency) }}</span>
        </div>
        <div v-else class="text-disabled">—</div>
      </template>

       <!-- Estado (derivado general) -->
       <template #item.rent_status="{ item }">
         <v-chip size="small" class="text-capitalize" :color="statusColor(item.rent_status)" variant="flat">
           {{ item.rent_status }}
         </v-chip>
       </template>

      <!-- Acciones -->
      <template #item.actions="{ item }">
        <div class="d-flex align-center gap-2">
          <v-btn v-if="item.rent_status === 'pending' &&  item._rent?.id" size="small" color="primary" variant="text" @click="onGenerateOne(item)" class="me-2">
          Sincronizar Cuota
        </v-btn>
          <v-btn v-if="item.rent_status === 'pending' &&  !item._rent?.id" size="small" color="primary" variant="text" @click="onGenerateOne(item)" class="me-2">
          Generar Cuota
        </v-btn>

        </div>
      </template>

      <!-- Footer: summary de página -->
      <template #bottom>
        <div class="d-flex justify-space-between align-center px-4 py-2 text-medium-emphasis">
          <div>
            Mostrando {{ pageFrom }}–{{ pageTo }} de {{ total }}
          </div>
          <div class="d-flex align-center gap-2">
            <v-select
              v-model="options.itemsPerPage"
              :items="[10, 25, 50, 100]"
              density="compact"
              hide-details
              style="max-width: 100px"
            />
          </div>
        </div>
      </template>
    </v-data-table-server>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue'
import axios from '@/services/axios' // ajustá la ruta si tu axiosInstance vive en otro lado
import { formatDate, formatPeriodWithMonthName } from '@/utils/date-formatter'
import { formatModelId } from '@/utils/models-formatter'
// import { useSnackbar } from '@/composables/useSnackbar' // opcional
// const { showMessage } = useSnackbar()

/**
 * PROPS
 * - period: 'YYYY-MM' (requerido por el backend)
 * - endpoint: base de endpoints (GET index, POST generate)
 * - search: objeto con filtros adicionales (ej. { tenant_id, property_id })
 * - filter: string ('', 'pending_rent', 'rent_pending', 'open')
 */
 const props = defineProps({
  period: {
    type: String,
    default: null
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['error', 'generated', 'open-rent'])

const items = ref([])
const total = ref(0)
const loading = ref(false)
const selected = ref([])

 const headers = [
   { title: 'ID', key: 'id', sortable: true },
  //  { title: 'Moneda', key: 'currency', sortable: false, width: 90 },
   { title: 'Importe planificado', key: 'last_planned_adjustment', sortable: false },
   { title: 'Importe cuota', key: 'rent_info', sortable: false, width: 220 },
   { title: 'Estado', key: 'rent_status', sortable: true, width: 140 },
   { title: 'Acciones', key: 'actions', sortable: false, width: 120 },
 ]

const options = reactive({
  page: 1,
  itemsPerPage: 25,
  sortBy: [{ key: 'id', order: 'asc' }],
})

const local = reactive({
  searchId: '',
  filter: props.filter || '',
})

const filterItems = [
  { label: 'Todos', value: '' },
  { label: 'Pendientes de renta', value: 'pending_rent' },
  { label: 'Renta en pending', value: 'rent_pending' },
  { label: 'Abiertos', value: 'open' },
]

// Helpers
function parseJsonMaybe(val) {
  if (!val) return null
  if (typeof val === 'object') return val
  try { return JSON.parse(val) } catch { return null }
}

function formatMoney(n, currency = 'ARS') {
  if (n == null) return '—'
  const value = Number(n)
  if (Number.isNaN(value)) return String(n)
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: currency || 'ARS',
    maximumFractionDigits: 2,
  }).format(value)
}

function statusColor(status) {
  switch (status) {
    case 'pending_adjustment': return 'orange'
    case 'pending': return 'grey'
    case 'generated': return 'blue'
    case 'issued': return 'teal'
    case 'posted': return 'green'
    case 'draft': return 'blue'
    case 'error': return 'red'
    default: return 'default'
  }
}

const pageFrom = computed(() => {
  if (total.value === 0) return 0
  return (options.page - 1) * options.itemsPerPage + 1
})
const pageTo = computed(() => {
  const x = options.page * options.itemsPerPage
  return x > total.value ? total.value : x
})

function onUpdateOptions(newOptions) {
  // v-data-table-server entrega {page, itemsPerPage, sortBy}
  options.page = newOptions.page
  options.itemsPerPage = newOptions.itemsPerPage
  options.sortBy = Array.isArray(newOptions.sortBy) ? newOptions.sortBy : []
  fetchData()
}

// debounce manual simple para la búsqueda
let searchTimer = null
function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    options.page = 1
    fetchData()
  }, 400)
}

async function fetchData() {
  if (!props.period) return
  loading.value = true
  try {
     const primarySort = options.sortBy?.[0] || { key: 'id', order: 'asc' }
     const params = {
       period: props.period, // 'YYYY-MM'
       page: options.page,
       per_page: options.itemsPerPage,
       sort_by: primarySort.key,
       sort_direction: primarySort.order,
      //  filter: local.filter,
       // merge de búsquedas
       ...props.filters
     }

     watch(props.filters, () => { options.page = 1; fetchData() }, { deep: true })
     const { data } = await axios.get('/api/contracts/rents', { params })

    // Normalizar filas: parsear JSON_OBJECT si vienen como string
    const raw = Array.isArray(data.data) ? data.data : []
    items.value = raw.map((r) => {
      const last = parseJsonMaybe(r.last_planned_adjustment)
      const rent = parseJsonMaybe(r.rent_info)
      return {
        ...r,
        _lastPlanned: last,
        _rent: rent,
      }
    })
    total.value = data?.meta?.total ?? raw.length
  } catch (err) {
    // showMessage?.('Error al cargar contratos', 'error')
    emit('error', err)
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function onBulkGenerate() {
  // if (selected.value.length === 0) return
  try {
    loading.value = true
    await axios.post(`/api/contracts/rents/generate`, {
      period: props.period
    })
    // showMessage?.('Rentas generadas/sincronizadas', 'success')
    emit('generated', { contract_ids: selected.value })
    selected.value = []
    fetchData()
  } catch (err) {
    // showMessage?.('No se pudieron generar/sincronizar', 'error')
    emit('error', err)
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function onGenerateOne(item) {
  try {
    loading.value = true
    await axios.post(`api/contracts/rents/generate`, {
      period: props.period,
      contract_ids: [item.id],
    })
    // showMessage?.('Renta generada/sincronizada', 'success')
    emit('generated', { contract_ids: [item.id] })
    fetchData()
  } catch (err) {
    // showMessage?.('No se pudo generar', 'error')
    emit('error', err)
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Auto-carga
watch(() => [props.period, props.filter], () => {
  local.filter = props.filter || ''
  options.page = 1
  fetchData()
}, { immediate: true })

onMounted(fetchData)

defineExpose({ onBulkGenerate, fetchData })
</script>

<style scoped>
.w-56 { max-width: 14rem; }
.w-64 { max-width: 16rem; }
</style>
