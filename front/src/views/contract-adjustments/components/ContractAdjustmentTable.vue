<template>
    <!-- Tabla -->
  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="adjustments"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchAdjustments"
  >
    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date) }}
    </template>

    <template #[`item.contract`]="{ item }">
      <!-- Contrato / Inquilino -->
      <div v-if="item.contract" class="d-flex flex-column">
          <span class="font-weight-medium">{{ item.contract.main_tenant.client.full_name}}</span>
          <small class="text-medium-emphasis"><RouterLink :to="`/contracts/${item.contract.id}`" class="text-primary text-decoration-none">
          {{ formatModelId(item.contract.id, 'CON') }} 
            <!-- {{ item.contract.id }} -->
        </RouterLink> ({{ formatDate(item.contract?.start_date) }})</small>
      </div>
      <span v-else class="text-grey">—</span>
    </template>
    
    <template #[`item.type`]="{ item }">
      <span v-if="item.type === 'index' && item.index_type">{{ item.index_type.code }}</span>
      <span v-else>{{ adjustmentLabel(item.type) }}</span>
    </template>

    <template #[`item.index_type`]="{ item }">
      <span v-if="item.type === 'index' && item.index_type">{{ item.index_type.name }}</span>
      <span v-else class="text-grey">—</span>
    </template>
    <template #[`item.value`]="{ item }">
      <template v-if="item.type === 'percentage'">{{ item.value !== null ? `${item.value}%` : '—' }}</template>
        <template v-else-if="item.type === 'fixed' || item.type === 'negotiated'">{{ item.value !== null ? formatMoney(item.value, item.contract?.currency || '$') : '—' }}</template>
          <template v-else-if="item.type === 'index'">
            <span v-if="item.value !== null">
                <a href="#" @click.prevent="showIndexCalc(item)" class="text-primary text-decoration-underline" title="Ver cálculo">
                {{ item.value }}%
              </a>
            </span>
            <span v-else class="text-grey">Pendiente</span>
          </template>
        <template v-else>—</template>
    </template>

    <!-- Nuevo valor -->
    <template #[`item.applied_amount`]="{ item }">
      <template v-if="item.applied_amount !== null">
        <div class="d-flex flex-column">
          <span class="font-weight-medium">{{ formatMoney(item.applied_amount, item.contract?.currency || '$') }}</span>
          <!-- {{ formatMoney(item.applied_amount, item.contract?.currency || '$') }} --> 
          <small class="text-medium-emphasis">BASE: {{ formatMoney(item.base_amount, item.contract?.currency || '$') }}</small>
        </div>
      </template>
      <template v-else><span class="text-grey">—</span></template>
    </template>


    <template #[`item.contract_start_date`]="{ item }">
      --  
    </template>

    <template #[`item.applied_at`]="{ item }">
      <div class="d-flex align-center">
        <template v-if="item.applied_at">
          <v-icon color="success" size="small" class="me-1">mdi-check-circle</v-icon>{{ formatDate(item.applied_at) }}
        </template>
        <template v-else-if="item.value !== null && new Date(item.effective_date) <= new Date()">
          <v-icon color="warning" size="small" class="me-1">mdi-clock-alert</v-icon><span class="text-warning">Pendiente</span>
        </template>
        <template v-else><span class="text-grey">—</span></template>
      </div>
    </template>

    <template #[`item.status`]="{ item }">
      <v-chip :color="statusColor(getStatus(item))" size="small" class="text-capitalize">{{ formatStatus(getStatus(item)) }}</v-chip>
    </template>

    <template #[`item.actions`]="{ item }">
      <div class="d-flex align-center">
        <v-btn v-if="item.type === 'index' && !item.value" size="small" color="primary" variant="text" :loading="assigningIndex === item.id" :disabled="assigningIndex !== null" @click="assignIndex(item)" class="me-2">
          <v-icon size="small" class="me-1">mdi-reload</v-icon> Asignar índice
        </v-btn>
        <v-btn v-if="item.value !== null && item.applied_at === null" size="small" color="success" variant="text" :loading="applyingAdjustment === item.id" :disabled="applyingAdjustment !== null" @click="applyAdjustment(item)" class="me-2">
          <v-icon size="small" class="me-1">mdi-check</v-icon> Aplicar
        </v-btn>
      </div>
    </template>
  </v-data-table-server>

  <!-- Modal de cálculo de índice -->
  <v-dialog v-model="showIndexCalcDialog" max-width="500">
    <v-card>
      <v-card-title class="text-h6">Cálculo de índice</v-card-title>
      <v-card-text>
        <!-- INSERT_YOUR_CODE -->
        <div v-if="indexCalcPeriod">
              Valor inicial: {{ indexCalcPeriod.index_audit.start.value }} ({{ formatDate(indexCalcPeriod.index_audit.start.date) }})<br>
              Valor final: {{ indexCalcPeriod.index_audit.final.value }} ({{ formatDate(indexCalcPeriod.index_audit.final.date) }})<br>
              Factor: {{ indexCalcPeriod.factor }}<br>
              Porcentaje: {{ indexCalcPeriod.value }}%<br>

        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="showIndexCalcDialog = false">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Dialogo de selección de período -->
  <v-dialog v-model="bulkDialog" max-width="400">
    <v-card>
      <v-card-title class="text-h6">{{ dialogTitle }}</v-card-title>
      <v-card-text>
        <v-text-field v-model="bulkPeriod" label="Período (YYYY-MM)" type="month" variant="solo-filled" hide-details />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="bulkDialog = false">Cancelar</v-btn>
        <v-btn color="primary" :loading="bulkLoading" @click="confirmBulkAction">Confirmar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Modal de resultados -->
  <v-dialog v-model="showBulkResultDialog" max-width="800">
    <v-card>
      <v-card-title class="text-h6">Resultados del procesamiento</v-card-title>
      <v-card-text>
        <!-- Resumen -->
        <v-alert type="info" class="mb-4">
          Total: {{ bulkResults.total }} ·
          Exitosos: {{ bulkResults.success.length }} ·
          Ignorados: {{ bulkResults.ignored.length }} ·
          Errores: {{ bulkResults.failed.length }}
        </v-alert>

        <!-- Pestañas -->
        <v-tabs v-model="activeTab" grow>
          <v-tab value="success">Exitosos</v-tab>
          <v-tab value="ignored">Ignorados</v-tab>
          <v-tab value="failed">Errores</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="success">
            <v-list dense>
              <v-list-item v-for="item in bulkResults.success" :key="'s-'+item.id">
                <v-list-item-title class="text-success">✅ Ajuste #{{ item.id }} (Contrato #{{ item.contract_id }})</v-list-item-title>
                <v-list-item-subtitle>{{ item.message }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-window-item>
          <v-window-item value="ignored">
            <v-list dense>
              <v-list-item v-for="item in bulkResults.ignored" :key="'i-'+item.id">
                <v-list-item-title class="text-grey">⏸ Ajuste #{{ item.id }} (Contrato #{{ item.contract_id }})</v-list-item-title>
                <v-list-item-subtitle>{{ item.message }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-window-item>
          <v-window-item value="failed">
            <v-list dense>
              <v-list-item v-for="item in bulkResults.failed" :key="'f-'+item.id">
                <v-list-item-title class="text-error">❌ Ajuste #{{ item.id }} (Contrato #{{ item.contract_id }})</v-list-item-title>
                <v-list-item-subtitle>{{ item.message }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-window-item>
        </v-window>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="showBulkResultDialog = false">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue'
import axios from '@/services/axios'
import { formatModelId } from '@/utils/models-formatter'
import { formatStatus, statusColor } from '@/utils/collections-formatter'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import { useSnackbar } from '@/composables/useSnackbar'

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

const snackbar = useSnackbar()

const adjustments = ref([])
const loading = ref(false)
const total = ref(0)
const assigningIndex = ref(null)
const applyingAdjustment = ref(null)
const showIndexCalcDialog = ref(false)
const indexCalcPeriod = ref(new Date().toISOString().slice(0, 7))

// Opciones y filtros
const options = reactive({ page: 1, itemsPerPage: 10, sortBy: [{ key: 'effective_date', order: 'asc' }] })


// Combos
const contractOptions = ref([])
const clientOptions = ref([])

// Bulk
const bulkDialog = ref(false)
const bulkPeriod = ref(new Date().toISOString().slice(0, 7))
const bulkAction = ref(null)
const bulkLoading = ref(false)
const showBulkResultDialog = ref(false)
const activeTab = ref('success')
const bulkResults = reactive({ total: 0, success: [], ignored: [], failed: [] })

const headers = [
  { title: 'Contrato / Inquilino', key: 'contract', sortable: false },
  { title: 'Fecha', key: 'effective_date', sortable: true },
  { title: 'Tipo', key: 'type', sortable: false },
  { title: 'Valor', key: 'value', sortable: true },
  { title: 'Nuevo Importe', key: 'applied_amount', sortable: true },
  { title: 'Aplicado', key: 'applied_at', sortable: true },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

// Opciones de filtros
const statusOptions = [
  { title: 'Pendiente', value: 'pending' },
  { title: 'Vencido sin valor', value: 'expired_without_value' },
  { title: 'Con valor', value: 'with_value' },
  { title: 'Aplicado', value: 'applied' },
]
const typeOptions = [
  { title: 'Fijo', value: 'fixed' },
  { title: 'Porcentaje', value: 'percentage' },
  { title: 'Índice', value: 'index' },
  { title: 'Negociado', value: 'negotiated' },
]

// Helpers
const dialogTitle = computed(() => ({
  assign: 'Asignar índices en lote',
  apply: 'Aplicar ajustes en lote',
  process: 'Procesar (Asignar + Aplicar)',
}[bulkAction.value] || ''))

const adjustmentLabel = (type) => ({ fixed: 'Monto fijo', percentage: 'Porcentaje', index: 'Índice', negotiated: 'Negociado' }[type] || type)

const getStatus = (a) => {
  const today = new Date().toISOString().split('T')[0]
  if (a.applied_at) return 'applied'
  if (a.value !== null) return a.effective_date <= today ? 'with_value' : 'pending'
  return a.effective_date <= today ? 'expired_without_value' : 'pending'
}

const showIndexCalc = (item) => {
  showIndexCalcDialog.value = true
  indexCalcPeriod.value = item
}

// Acciones individuales
const assignIndex = async (item) => {
  assigningIndex.value = item.id
  try {
    await axios.post(`/api/contract-adjustments/${item.id}/assign-index`)
    snackbar.success('Índice asignado correctamente')
    fetchAdjustments()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al asignar índice')
  } finally {
    assigningIndex.value = null
  }
}

const applyAdjustment = async (item) => {
  applyingAdjustment.value = item.id
  try {
    await axios.post(`/api/contract-adjustments/${item.id}/apply`)
    snackbar.success('Ajuste aplicado correctamente')
    fetchAdjustments()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al aplicar ajuste')
  } finally {
    applyingAdjustment.value = null
  }
}

// Bulk
const openBulkDialog = (action) => { bulkAction.value = action; bulkDialog.value = true }

const confirmBulkAction = async () => {
  try {
    if (!bulkPeriod.value) throw new Error('Debe seleccionar un período')
    bulkLoading.value = true
    const endpoint =
      bulkAction.value === 'assign'
        ? '/api/contract-adjustments/assign-index/bulk'
        : bulkAction.value === 'apply'
        ? '/api/contract-adjustments/apply/bulk'
        : '/api/contract-adjustments/process/bulk'

    const { data } = await axios.post(endpoint, { period: bulkPeriod.value })
    if (bulkAction.value === 'process') {
      bulkResults.total = data.total
      bulkResults.success = [...data.results.assigned.success, ...data.results.applied.success]
      bulkResults.ignored = [...(data.results.assigned.ignored || []), ...(data.results.applied.ignored || [])]
      bulkResults.failed = [...data.results.assigned.failed, ...data.results.applied.failed]
    } else {
      bulkResults.total = data.total
      bulkResults.success = data.results.success
      bulkResults.ignored = data.results.ignored
      bulkResults.failed = data.results.failed
    }

    activeTab.value = 'success'
    showBulkResultDialog.value = true
    snackbar.success('Proceso completado. Revise el detalle.')
    bulkDialog.value = false
    fetchAdjustments()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error en acción masiva')
  } finally {
    bulkLoading.value = false
  }
}

// Fetch data
const fetchAdjustments = async () => {
  loading.value = true
  try {
    const params = { page: options.page, per_page: options.itemsPerPage, ...props.filters }
    if (props.period) {
      params.period = props.period
    }
    const { data } = await axios.get('/api/contract-adjustments/global', { params })
    adjustments.value = data.data
    total.value = data.meta.total
  } catch (e) {
    snackbar.error('Error al cargar ajustes')
  } finally {
    loading.value = false
  }
}
const fetchContracts = async () => {
  const { data } = await axios.get('/api/contracts', { params: { per_page: 100 } })
  contractOptions.value = data.data.map(c => ({ title: `Contrato ${c.id}`, value: c.id }))
}
const fetchClients = async () => {
  const { data } = await axios.get('/api/clients', { params: { per_page: 100 } })
  clientOptions.value = data.data.map(c => ({ title: `${c.name} ${c.last_name}`, value: c.id }))
}

watch(props.filters, () => { options.page = 1; fetchAdjustments() }, { deep: true })

watch(() => props.period, () => {
  const firstDay = new Date(Date.UTC(
    parseInt(props.period.split('-')[0]),
    parseInt(props.period.split('-')[1]) - 1,
    1
  ));

  const lastDay = new Date(Date.UTC(
    parseInt(props.period.split('-')[0]),
    parseInt(props.period.split('-')[1]),
    0
  ));

  console.log(firstDay.toISOString()); // 2025-12-01T00:00:00.000Z
  console.log(lastDay.toISOString());  // 2025-12-31T00:00:00.000Z
  console.log(lastDay.toISOString());  // 2025-12-31T00:00:00.000Z
  console.log(firstDay.getDate());     // 1
  console.log(lastDay.getDate());      // 31


  props.filters.effective_date_from = props.period ? `${props.period}-01` : null
  props.filters.effective_date_to =lastDay.toISOString().split('T')[0];
  fetchAdjustments()
 }, { immediate: true })

console.log('props.period')
console.log(props.period)
onMounted(() => { fetchContracts(); fetchClients(); fetchAdjustments() })
defineExpose({ openBulkDialog })
</script>
