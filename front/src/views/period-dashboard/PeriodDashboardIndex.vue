<template>
  <div>
    <!-- Header & Filtros -->
    <div class="mb-8 d-flex align-center justify-space-between">
      <div>
        <h2 class="text-h5">Tablero del período</h2>
        <div class="text-medium-emphasis">Foto operativa del mes seleccionado</div>
      </div>
      <div class="d-flex gap-3 align-center">
        <v-text-field
          v-model="period"
          label="Período (YYYY-MM)"
          variant="outlined"
          density="comfortable"
          style="max-width: 180px"
        />
        <v-select
          v-model="currency"
          :items="currencyItems"
          label="Moneda"
          variant="outlined"
          density="comfortable"
          style="max-width: 160px"
        />
        <v-switch v-model="onlyPending" label="Sólo pendientes" inset />
        <v-btn color="primary" @click="reloadAll" :loading="loading.summary">Actualizar</v-btn>
      </div>
    </div>

    <!-- KPIs -->
    <v-row dense class="mb-8">
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="Contratos activos" :value="summary.totals.contracts_active" :loading="loading.summary" tooltip="Contratos en estado activo durante el mes" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="Ajustes pendientes" :value="summary.totals.adjustments?.pending || 0" :loading="loading.summary" tooltip="Ajustes del mes sin aplicar" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="RENT faltantes" :value="summary.totals.rent?.missing || 0" :loading="loading.summary" tooltip="Contratos sin renta generada" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard title="LQI a generar" :value="summary.totals.lqi?.to_generate || 0" :loading="loading.summary" tooltip="Contratos con cargos sin LQI" />
      </v-col>
    </v-row>

    <!-- Secciones -->
    <v-expansion-panels variant="accordion" multiple>
      <v-expansion-panel value="rents">
        <v-expansion-panel-title>Rentas del período</v-expansion-panel-title>
        <v-expansion-panel-text>
          <RentsTable :period="period" :currency="currency" />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <v-expansion-panel value="lqi">
        <v-expansion-panel-title>LQI del período</v-expansion-panel-title>
        <v-expansion-panel-text>
          <LqiTable :period="period" :currency="currency" />
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'
import KpiCard from './components/KpiCard.vue'
import RentsTable from './components/RentsTable.vue'
import LqiTable from './components/LqiTable.vue'

const currencyItems = ['ALL', 'ARS', 'USD']
const period = ref(new Date().toISOString().slice(0,7))
const currency = ref('ALL')
const onlyPending = ref(false)

const summary = reactive({
  period: '',
  currency: 'ALL',
  totals: {
    contracts_active: 0,
    adjustments: { scheduled: 0, applied: 0, pending: 0 },
    rent: { expected: 0, generated: 0, missing: 0, duplicates: 0 },
    lqi: { to_generate: 0, draft: 0, issued: 0, with_balance: 0 },
    collections: { invoiced: 0, collected: 0, rate: 0 },
    liq_owners: { issued: 0 },
    rpg: { executed: 0 },
  }
})

const loading = reactive({ summary: false })

async function loadSummary() {
  loading.summary = true
  try {
    const { data } = await axios.get('/api/dashboard/summary', {
      params: { period: period.value, currency: currency.value }
    })
    Object.assign(summary, data)
  } finally {
    loading.summary = false
  }
}

function reloadAll() {
  loadSummary()
}

onMounted(() => {
  loadSummary()
})
</script>
