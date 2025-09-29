<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="mb-1">Gestión de Liquidaciones (LQI)</h2>
        <p class="text-medium-emphasis">
          Generá, sincronizá y emití las liquidaciones de inquilinos por contrato.
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-refresh"
        :loading="syncing"
        :disabled="!canGenerate"
        @click="generateForPeriod"
      >
        Generar liquidaciones del período
      </v-btn>
    </div>

    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="4">
            <ContractAutocomplete
              v-model="filters.contract_id"
              label="Contrato"
              flat
              variant="solo-filled"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-text-field
              v-model="filters.period"
              type="month"
              label="Período (YYYY-MM)"
              variant="solo-filled"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="6" md="2">
            <CurrencySelect
              v-model="filters.currency"
              label="Moneda"
              variant="solo-filled"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="filters.status"
              :items="statusOptions"
              item-title="title"
              item-value="value"
              label="Estado"
              variant="solo-filled"
              hide-details
              clearable
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <LqiTable ref="tableRef" :filters="filters" />
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import LqiTable from './components/LqiTable.vue'
import { syncLiquidations } from '@/services/lqiService'

const snackbar = useSnackbar()

const filters = reactive({
  contract_id: null,
  period: currentPeriod(),
  currency: 'ARS',
  status: null,
})

const statusOptions = [
  { title: 'Borrador', value: 'draft' },
  { title: 'Emitido', value: 'issued' },
]

const tableRef = ref(null)
const syncing = ref(false)

const canGenerate = computed(() => Boolean(filters.contract_id && filters.period && filters.currency))

async function generateForPeriod() {
  if (!canGenerate.value) {
    snackbar.warning('Seleccioná contrato, período y moneda para generar las liquidaciones')
    return
  }

  syncing.value = true
  try {
    await syncLiquidations(filters.contract_id, {
      period: filters.period,
      currency: filters.currency,
    })
    snackbar.success('Liquidación sincronizada correctamente')
    tableRef.value?.reload()
  } catch (error) {
    snackbar.error(extractErrorMessage(error) ?? 'No se pudo generar la liquidación')
  } finally {
    syncing.value = false
  }
}

function currentPeriod() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  return `${now.getFullYear()}-${month}`
}

function extractErrorMessage(error) {
  if (error?.response?.data?.message) return error.response.data.message
  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0]
    if (first) return first
  }
  return error?.message
}
</script>
