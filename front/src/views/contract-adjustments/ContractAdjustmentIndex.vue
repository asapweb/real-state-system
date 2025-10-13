<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5"><a href="/contracts" class="text-medium-emphasis text-decoration-none font-weight-light">Contratos </a >/ Gestion de Ajustes</h2>
    </div>
    <div class="text-h6">
      <span class=" text-grey text-decoration-none font-weight-light">PERIODO </span ><span class=" font-weight-bold text-uppercase">{{ formatPeriodWithMonthName(period) }}</span>
    </div>
  </div>

  <!-- Kpis -->
  <v-row dense class="mb-8">
    <v-col cols="12" sm="6" md="3">
      <KpiCard
        title="Contratos activos"
        tooltip="Total de contratos que están vigentes durante el período seleccionado."
        :value="summary.totals.active_contracts || 0"
        :loading="loading.summary"
      />
    </v-col>
    <v-col cols="12" sm="6" md="3">
      <KpiCard
        title="Ajustes"
        tooltip="Número total de contratos que tienen un ajuste de alquiler configurado para el período seleccionado."
        :value="summary.totals.adjustments || 0"
        :loading="loading.summary"
      />
    </v-col>
   
    <v-col cols="12" sm="6" md="3">
      <KpiCardProgressLine
      title="Cobertura"
      tooltip="Porcentaje de ajustes que tienen un valor asignado y han sido aplicados."
      :value="summary.totals.adjustments_with_value_coverege * 100 || 0"
      :subtitle="summary.totals.adjustments_applied + ' de ' + (summary.totals.adjustments_with_value)"
      :loading="loading.summary"
      />
    </v-col>
    <v-col cols="12" sm="6" md="3">
      <KpiCardProgressLine
      title="Cobertura del período"
      tooltip="Porcentaje de todos los ajustes del período que han sido aplicados exitosamente. "
      :value="summary.totals.adjustments_coverege * 100 || 0"
      :subtitle="summary.totals.adjustments_applied + ' de ' + (summary.totals.adjustments)"
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
        <v-btn  color="secondary" 
          variant="tonal"
          size="small"
           @click="adjustmentTable.openBulkDialog('assign')">
          Asignar
        </v-btn>
        <v-btn  color="secondary" 
          variant="tonal"
          size="small"
           @click="adjustmentTable.openBulkDialog('apply')"
          class="ml-1">
          Aplicar
        </v-btn>
        <v-btn color="primary" 
          variant="tonal"
          size="small"
           @click="adjustmentTable.openBulkDialog('process')"
          class="ml-1">
          Asignar y aplicar
        </v-btn>

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
        <v-text-field v-model="periodInput" label="Período (YYYY-MM)" type="month" variant="outlined" hide-details class="mb-4 " flat />
        <v-select density="compact" v-model="filters.type" :items="typeOptions" item-title="title" item-value="value" label="Tipo" variant="outlined" flat hide-details clearable class="mb-4 " />
    <ContractAutocomplete v-model="filters.contract_id" label="Contrato" variant="outlined" flat hide-details class="mb-4 " />
    
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
  <ContractAdjustmentTable ref="adjustmentTable" :period="period" :filters="filters" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive, onBeforeMount } from 'vue'
import KpiCard from '@/views/components/KpiCard.vue'
import KpiCardProgressLine from '@/views/components/KpiCardProgressLine.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'
import { useRoute } from 'vue-router'
import axios from '@/services/axios'
import { formatPeriodWithMonthName } from '@/utils/date-formatter'
const summary = reactive({ period: null, totals: {} })
const loading = reactive({ summary: false, list: false })
const adjustmentTable = ref(null)
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
  
  return count
})
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
    const { data } = await axios.get('/api/contracts/adjustments/summary', { params: { period: periodInput.value } })
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
