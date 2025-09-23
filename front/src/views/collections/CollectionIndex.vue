<template>
  <div>
    <!-- Encabezado -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="mb-1">Liquidaciones de Inquilinos</h2>
        <p class="text-medium-emphasis">
          Gestiona cobranzas mensuales, borradores y facturas emitidas.
        </p>
      </div>
    </div>

    <!-- Filtros -->
    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" sm="2">
            <v-select
              v-model="filters.period"
              :items="availablePeriods"
              label="Período"
              flat
              variant="solo-filled"
            />
          </v-col>
          <v-col cols="12" sm="2">
            <v-select
              v-model="filters.status"
              :items="statuses"
              label="Estado"
              flat
              variant="solo-filled"
              clearable
            />
          </v-col>
          <v-col cols="12" sm="3">
            <ClientAutocomplete v-model="filters.client_id" label="Inquilino" variant="solo-filled" flat hide-details />
          </v-col>
          <v-col cols="12" sm="3">
            <ContractAutocomplete v-model="filters.contract_id" label="Contrato" variant="solo-filled" flat hide-details />
          </v-col>
          <v-col cols="12" sm="2">
            <v-select
              v-model="filters.currency"
              :items="currencies"
              label="Moneda"
              variant="solo-filled"
              flat
              clearable
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabla de cobranzas -->
    <CollectionTable
      ref="tableRef"
      :filters="filters"
      @apply-adjustment="onApplyAdjustment"
      @open-editor="onOpenEditor"
      @view-issued="onViewIssued"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import CollectionTable from './components/CollectionTable.vue'

const filters = reactive({
  period: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`,
  status: null,
  client_id: null,
  contract_id: null,
  currency: null,
})

const statuses = [
  { title: 'Pending', value: 'pending' },
  { title: 'Draft', value: 'draft' },
  { title: 'Issued', value: 'issued' },
]

const currencies = [
  { title: 'ARS', value: 'ARS' },
  { title: 'USD', value: 'USD' },
]

const availablePeriods = ref([]) // cargado desde backend
const tableRef = ref(null)

// Acciones de tabla
const onApplyAdjustment = (item) => {
  // Redirigir a módulo de ajustes filtrando por contrato
  window.location.href = `/contracts/adjustments?contract=${item.contract_id}`
}

const onOpenEditor = (item) => {
  // Abrir editor de cobranza para contrato/período
  window.location.href = `/collections/${item.contract_id}/editor?period=${item.period}`
}

const onViewIssued = (item) => {
  // Abrir vista de solo lectura para cobranzas emitidas
  window.location.href = `/collections/${item.contract_id}/view?period=${item.period}`
}

onMounted(() => {
  // Inicializar períodos disponibles (ej: últimos 12 meses + mes actual)
  const today = new Date()
  for (let i = 0; i < 12; i++) {
    const d = new Date(today.getFullYear(), today.getMonth() - i, 1)
    availablePeriods.value.push({
      title: d.toLocaleString('default', { month: 'long', year: 'numeric' }),
      value: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
    })
  }
})
</script>
