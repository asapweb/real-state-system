<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5"><a href="/contracts" class="text-medium-emphasis text-decoration-none font-weight-light">Contratos </a >/ Gestion de Cargos</h2>
    </div>
    <div class="text-h6">
      <span class=" text-grey text-decoration-none font-weight-light">PERIODO </span ><span class=" font-weight-bold text-uppercase">{{ formatPeriodWithMonthName(period) }}</span>
    </div>
  </div>


  <div>


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
        <v-btn color="primary" variant="tonal" density="compact"  :to="{ name: 'ContractChargeCreate' }">
        <v-icon size="small" class="me-1">mdi-plus</v-icon>
        Nuevo cargo
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
        <!-- Filtro de Período -->
        <v-text-field
          v-model="periodInput"
          label="Período (YYYY-MM)"
          type="month"
          variant="outlined"
          density="compact"
          class="mb-4"
          hide-details
        ></v-text-field>

        <!-- Filtro de Contrato -->
        <ContractAutocomplete
          v-model="filters.contract_id"
          label="Contrato"
          variant="outlined"
          density="compact"
          class="mb-4"
          :menu-props="{ maxHeight: '300px' }"
          :item-props="{ style: 'white-space: normal; line-height: 1.2; padding: 12px 20px;' }"
        />

        <!-- Filtro de Tipo de Cargo -->
        <ChargeTypeSelect
          v-model="filters.charge_type_id"
          variant="outlined"
          density="compact"
          class="mb-4"
        />

        <!-- Filtro de Tipo de Servicio -->
        <ServiceTypeSelect
          v-model="filters.service_type_id"
          variant="outlined"
          density="compact"
          class="mb-4"
        />

        <!-- Filtro de Moneda -->
        <CurrencySelect
          v-model="filters.currency"
          variant="outlined"
          density="compact"
          class="mb-4"
        />

        <!-- Filtro de Estado -->
        <v-select
          v-model="filters.status"
          :items="statusOptions"
          item-title="title"
          item-value="value"
          label="Estado"
          variant="outlined"
          density="compact"
          class="mb-4"
          hide-details
        ></v-select>

        <!-- Filtro de Descripción -->
        <v-text-field
          v-model="filters.search_description"
          label="Descripción contiene"
          variant="outlined"
          density="compact"
          clearable
          class="mb-4"
        ></v-text-field>
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

// Filtros drawer
const filterDrawer = ref(false)

// Contar filtros activos
const activeFilterCount = computed(() => {
  let count = 0
  if (filters.contract_id) count++
  if (filters.charge_type_id) count++
  if (filters.service_type_id) count++
  if (filters.currency) count++
  if (filters.status) count++
  if (filters.search_description) count++
  return count
})

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

// Función para limpiar filtros
function clearFilters() {
  filters.contract_id = null
  filters.charge_type_id = null
  filters.service_type_id = null
  filters.currency = null
  filters.status = null
  filters.search_description = null
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
