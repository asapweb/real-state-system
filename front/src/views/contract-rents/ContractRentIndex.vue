<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5"><a href="/contracts" class="text-medium-emphasis text-decoration-none font-weight-light">Contratos </a >/ Gestion de Cuotas</h2>
    </div>
    <div class="text-h6 me-2">{{ formatPeriodWithMonthName(period).charAt(0).toUpperCase() + formatPeriodWithMonthName(period).slice(1) }}</div>
  </div>

  <!-- Kpis -->
  <v-row dense class="mb-12">
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCard
        title="Contratos activos"
        tooltip="Contratos con vigencia en el período."
        :value="summary.totals.active_contracts || 0"
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
      <KpiCard
        title="Cuotas generadas"
        tooltip="Contratos con cuota creada (cualquier estado)."
        :value="summary.totals.rents_generated || 0"
        :loading="loading.summary"
      />

    </v-col>
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCard
      title="Pendientes de generación"
      tooltip="Sin ajuste pendiente y sin cuota creada."
      :value="summary.totals.pending_generation || 0"
      :loading="loading.summary"
      />
    </v-col>
    <v-col cols="12" sm="auto" class="flex-grow-1">
      <KpiCard
      title="Cobertura de generación"
      tooltip="% de habilitados (sin ajuste) que ya generaron cuota."
      :value="summary.totals.coverage_ratio * 100 + '%' || 0"
      :loading="loading.summary"
      />
    </v-col>
  </v-row>

  <!-- Acciones -->
  <div class="d-flex justify-space-between">
    <div>
      <v-btn-toggle v-model="showFilters" color="secondary" density="compact" divided>
        <v-btn  icon="mdi-filter" value="filters" density="comfortable" class=""></v-btn>
      </v-btn-toggle>
      <v-btn-group color="secondary" density="compact" class="mr-2" >
        </v-btn-group>
        <v-btn-group color="secondary" density="compact" divided>
          <v-btn  icon="mdi-chevron-left" color="secondary" density="comfortable" @click="changePeriod(-1)"></v-btn>
          <v-btn  icon="mdi-chevron-right" color="secondary" density="comfortable"  @click="changePeriod(1)"></v-btn>
        </v-btn-group>
        <v-btn-group color="secondary" density="compact" class="ml-1" divided>
          <v-btn  color="secondary" density="comfortable"  @click="resetToCurrentMonth">ESTE MES</v-btn>
        </v-btn-group>
    </div>
    <div>
      <v-btn-group color="secondary" density="compact" class="ml-1" divided>
        <v-btn color="primary" density="comfortable" @click="rentTable.onBulkGenerate('')">Generar / Sincronizar Cuotas</v-btn>
      </v-btn-group>
      <v-btn-group color="secondary" density="compact" class="ml-1" divided>
        <v-btn  icon="mdi-refresh" color="secondary" density="comfortable"  @click="rentTable.fetchData()"></v-btn>
      </v-btn-group>
    </div>
  </div>

  <!-- Filtros -->
  <div class="d-flex align-center mt-4" v-if="showFilters === 'filters'">
    <ContractAutocomplete v-model="filters.contract_id" label="Contrato" variant="solo-filled" flat hide-details class="ml-2" style="min-width: 300px" />
    <v-text-field v-model="periodInput" label="Período (YYYY-MM)" type="month" variant="solo-filled" hide-details class="ml-2" flat style="max-width: 220px" />
    <v-select v-model="filters.status" :items="statusOptions" item-title="title" item-value="value" label="Estado" variant="solo-filled" flat hide-details clearable style="max-width: 220px" class="ml-2" />
  </div>


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
</script>
