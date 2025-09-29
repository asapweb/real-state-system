<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5"><a href="/contracts" class="text-medium-emphasis text-decoration-none font-weight-light">Contratos </a >/ Gestion de Cargos</h2>
    </div>
    <div class="text-h6 me-2">{{ formatPeriodWithMonthName(period).charAt(0).toUpperCase() + formatPeriodWithMonthName(period).slice(1) }}</div>
  </div>


  <div>


    <!-- Acciones -->
  <div class="d-flex justify-space-between mb-4">
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
        <v-btn color="primary" :to="{ name: 'ContractChargeCreate' }">
        <v-icon size="small" class="me-1">mdi-plus</v-icon>
        Nuevo cargo
      </v-btn>
      </v-btn-group>
    </div>
  </div>

    <!-- Filtros -->
<div class="d-flex align-center mt-4" v-if="showFilters === 'filters'">
        <v-row dense>
          <v-col cols="12"  >
            <ContractAutocomplete v-model="filters.contract_id" label="Contrato" variant="solo-filled" flat hide-details />
          </v-col>
          <v-col cols="12" sm="7" md="4">
            <ChargeTypeSelect v-model="filters.charge_type_id" />
          </v-col>
          <v-col cols="12" sm="5" md="4">
            <ServiceTypeSelect v-model="filters.service_type_id" />
          </v-col>
          <v-col cols="12" sm="7" md="4">
            <CurrencySelect v-model="filters.currency" />
          </v-col>
          <v-col cols="12" sm="5" md="4">
            <v-select
              v-model="filters.status"
              :items="statusOptions"
              label="Estado"
              variant="solo-filled"
              density="compact"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field v-model="periodInput" label="Período (YYYY-MM)" type="month" variant="solo-filled" hide-details class="" flat style="" />
          </v-col>
          <v-col cols="12" md="8">
            <v-text-field v-model="filters.search_description" label="Descripción contiene" variant="solo-filled" clearable />
          </v-col>
        </v-row>
</div>
    <!-- Tabla de cargos -->
    <ContractChargeTable :filters="filters" />
  </div>

</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import ContractChargeTable from './components/ContractChargeTable.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import ServiceTypeSelect from '@/views/components/form/ServiceTypeSelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import { formatPeriodWithMonthName } from '@/utils/date-formatter'
import ChargeTypeSelect from '@/views/components/form/ChargeTypeSelect.vue'
const now = new Date()
const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const periodInput = ref(defaultMonth)
const period = computed(() => periodInput.value || null)
const showFilters = ref(false)
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

const filters = reactive({
  contract_id: null,
  service_type_id: null,
  currency: null,
  status: 'active',
  search_description: '',
  period: computed(() => periodInput.value || null),
})

const statusOptions = [
  { title: 'Activos', value: 'active' },
  { title: 'Cancelados', value: 'canceled' },
  { title: 'Todos', value: 'all' },
]
</script>

<style scoped>
</style>
