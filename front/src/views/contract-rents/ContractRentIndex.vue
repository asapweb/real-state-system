<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5"><a href="/contracts" class="text-medium-emphasis text-decoration-none font-weight-light">Contratos </a >/ Gestion de Cuotas</h2>
    </div>
    <div class="text-h6">
      <span class=" text-grey text-decoration-none font-weight-light">PERIODO </span ><span class=" font-weight-bold text-uppercase">{{ formatPeriodWithMonthName(period) }}</span>
    </div>
  </div>

  <!-- Kpis -->
  <v-row dense class="mb-8">
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCard
        title="Contratos activos"
        tooltip="Contratos con vigencia en el período."
        :value="summary.totals.active_contracts || 0"
        :subtitle="`${summary.totals.coverage_den} elegibles`"
        :loading="loading.summary"
      />
    </v-col>
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCard
        title="Ajustes pendientes"
        tooltip="Tienen ajuste del mes sin aplicar."
        :value="summary.totals.pending_adjustments || 0"
        :loading="loading.summary"
        @click="goToAdjustments"
      />
    </v-col>
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCardProgressLine
      title="Cobertura de generación"
      tooltip="% de habilitados (sin ajuste) que ya generaron cuota."
      :value="summary.totals.coverage_ratio * 100 || 0"
      :subtitle="summary.totals.rents_generated + ' de ' + (summary.totals.active_contracts - summary.totals.pending_adjustments)"
      :loading="loading.summary"
      />
    </v-col>
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCardProgressLine
      title="Cobertura del período"
      tooltip="% de habilitados (sin ajuste) que ya generaron cuota."
      :value="summary.totals.rents_generated / summary.totals.active_contracts * 100 || 0"
      :subtitle="summary.totals.rents_generated + ' de ' + (summary.totals.active_contracts )"
      :loading="loading.summary"
      />
    </v-col>
  </v-row>

<!-- Acciones -->
  <v-sheet class="mb-4" border rounded>
    <v-toolbar flat color="transparent" class="px-2">
      <v-btn
          @click="filterDrawer = !filterDrawer"
          prepend-icon="mdi-filter-variant"
        size="small"
          variant="outlined"
          color="grey-darken-1"
        >
          Filtros
          <v-badge
            v-if="activeFilterCount > 0"
            :content="activeFilterCount"
            color="primary"
            floating
            class="ml-2"
          ></v-badge>
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          variant="tonal"
          size="small"
           @click="rentTable.onBulkGenerate('')">Generar / Sincronizar Cuotas</v-btn>
           <v-btn  variant="tonal" density="compact" icon="mdi-refresh" color="secondary" size="small"  @click="rentTable.fetchData()"></v-btn>
    </v-toolbar>
  </v-sheet>

  <!-- Drawer de filtros -->
  <v-navigation-drawer
    v-model="filterDrawer"
    temporary
    location="right"
    width="600"
  >
    <v-toolbar color="transparent">
      <v-toolbar-title>Filtros</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="filterDrawer = false">
        <v-icon>mdi-close</v-icon>
      </v-btn>
    </v-toolbar>

    <v-card-text class="pa-4">
      <v-form>
        <v-text-field v-model="periodInput" label="Período (YYYY-MM)" type="month" variant="outlined" density="compact" hide-details class="mb-4" flat />
        <ContractAutocomplete v-model="filters.contract_id" label="Contrato" variant="outlined" density="compact" flat hide-details class="mb-4" />
        <v-select v-model="filters.status" :items="statusOptions" item-title="title" item-value="value" label="Estado" variant="outlined" density="compact" flat hide-details clearable class="mb-4" />

      </v-form>
    </v-card-text>

    <v-card-actions class="pa-4">
      <v-btn
        variant="outlined"
        color="grey"
        @click="clearFilters"
        class="mr-2"
      >
        Limpiar
      </v-btn>
      <v-spacer></v-spacer>
      <v-btn
        color="primary"
        @click="filterDrawer = false"
      >
        Aplicar
      </v-btn>
    </v-card-actions>
  </v-navigation-drawer>

  <!-- Tabla -->
   <div class="mt-8">
  <ContractRentTable ref="rentTable" :period="period" :filters="filters" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive, onBeforeMount } from 'vue'
import KpiCard from '@/views/components/KpiCard.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import ContractRentTable from './components/ContractRentTable.vue'
// import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { formatPeriodWithMonthName } from '@/utils/date-formatter'
import KpiCardProgressLine from '../components/KpiCardProgressLine.vue'
const rentTable = ref(null)
const summary = reactive({ period: null, totals: {} })
const loading = reactive({ summary: false, list: false })
// const period = ref(useRoute().query.period)
const now = new Date()
const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const periodInput = ref(defaultMonth)
const period = computed(() => periodInput.value || null)
const showFilters = ref(false)
const route = useRoute()
const filters = reactive({ status: null, type: null, contract_id: null, client_id: null, effective_date_from: null, effective_date_to: null })

// Filtros drawer
const filterDrawer = ref(false)

// Contar filtros activos
const activeFilterCount = computed(() => {
  let count = 0
  if (filters.status) count++
  if (filters.type) count++
  if (filters.contract_id) count++
  if (filters.client_id) count++
  if (filters.effective_date_from) count++
  if (filters.effective_date_to) count++
  return count
})
// Opciones de filtros
const statusOptions = [
  { title: 'Todos', value: '' },
  { title: 'Pendientes de renta', value: 'pending_rent' },
  { title: 'Renta en pending', value: 'rent_pending' },
  { title: 'Abiertos', value: 'open' },
]
const router = useRouter()
const goToAdjustments = () => {
  router.push({ name: 'contract.adjustments.index', query: { period: period.value } })
}
const changePeriod = (direction) => {
  const [year, month] = period.value.split('-').map(Number)
  const newMonth = month + direction
  if (newMonth < 1) {
    periodInput.value = `${year - 1}-12`
  } else if (newMonth > 12) {
    periodInput.value = `${year + 1}-01`
  } else {
    periodInput.value = `${year}-${String(newMonth).padStart(2, '0')}`
  }
}
const resetToCurrentMonth = () => {
  periodInput.value = new Date().toISOString().slice(0, 7)
}

 watch(periodInput, () => {
  fetchSummary() }, { deep: true })


onBeforeMount(() => {
  const periodQuery = route.query.period

  periodInput.value = defaultMonth
  if (typeof periodQuery === 'string' && /^\d{4}-\d{2}$/.test(periodQuery)) {
    periodInput.value = periodQuery
  }
  fetchSummary()
})
// Fetch summary
const fetchSummary = async () => {
  if (!periodInput.value) return
  loading.summary = true
  try {
    const { data } = await axios.get('/api/contracts/rents/summary', { params: { period: periodInput.value } })
    summary.period = data.period
    summary.totals = data.totals || {}
  } catch (e) {
    console.error(e)
    showError('Error al cargar el resumen')
  } finally {
    loading.summary = false
  }
}

// Función para limpiar filtros
function clearFilters() {
  filters.status = null
  filters.type = null
  filters.contract_id = null
  filters.client_id = null
  filters.effective_date_from = null
  filters.effective_date_to = null
}
</script>

<style scoped>
/* Mejorar visualización de texto largo en el drawer */
:deep(.v-navigation-drawer .v-list-item__content) {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.3;
}

:deep(.v-navigation-drawer .v-autocomplete .v-field__input) {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.3;
}

:deep(.v-navigation-drawer .v-autocomplete .v-list-item) {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.3;
  min-height: auto !important;
  padding: 12px 20px !important;
}
</style>
