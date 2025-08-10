<template>
  <v-container fluid>
    <v-row align="center" justify="space-between" class="mb-4">
      <v-col cols="12" md="6">
        <h1 class="text-h5 font-weight-bold">Movimientos de Caja</h1>
      </v-col>
      <v-col cols="12" md="6" class="text-end">
        <v-btn color="primary" @click="openCreate">
          + Nuevo movimiento
        </v-btn>
      </v-col>
    </v-row>

    <v-row class="mb-2" align="center">
      <v-col cols="12" md="3">
        <v-autocomplete
          v-model="filters.cash_account_id"
          :items="cashAccountOptions"
          :loading="loadingAccounts"
          item-title="name"
          item-value="id"
          label="Caja"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.direction"
          :items="directionOptions"
          label="Dirección"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-text-field
          v-model="filters.search"
          label="Buscar concepto o referencia"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-text-field
          v-model="filters.date_from"
          label="Fecha desde"
          type="date"
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-text-field
          v-model="filters.date_to"
          label="Fecha hasta"
          type="date"
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.currency"
          :items="currencyOptions"
          label="Moneda"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
    </v-row>

    <cash-movement-table
      :items="items"
      :loading="loading"
      :total="total"
      :options="options"
      @update:options="onOptionsUpdate"
      @delete-cash-movement="onDeleteCashMovement"
      @show-cash-movement="onShowCashMovement"
    />

    <!-- Modal para alta/edición de movimiento -->
    <v-dialog v-model="openForm" max-width="500">
      <cash-movement-form
        v-if="openForm"
        :initial-data="editingMovement"
        :loading="formLoading"
        @submit="onFormSubmit"
        @cancel="onFormCancel"
      />
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import CashMovementTable from './components/CashMovementTable.vue';
import CashMovementForm from './components/CashMovementForm.vue';
import CashAccountService from '@/services/CashAccountService';
import CashMovementService from '@/services/CashMovementService';
import { useSnackbar } from '@/composables/useSnackbar';

const items = ref([]);
const total = ref(0);
const loading = ref(false);
const openForm = ref(false);
const formLoading = ref(false);
const editingMovement = ref(null);
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'date', order: 'desc' }],
});

const filters = reactive({
  cash_account_id: null,
  direction: null,
  date_from: '',
  date_to: '',
  search: '',
  currency: null,
});

const directionOptions = [
  { title: 'Ingreso', value: 'in' },
  { title: 'Egreso', value: 'out' },
];

const cashAccountOptions = ref([]);
const loadingAccounts = ref(false);

const currencyOptions = [
  { title: 'Pesos Argentinos (ARS)', value: 'ARS' },
  { title: 'Dólares Estadounidenses (USD)', value: 'USD' },
];

let debounceTimeout;
const snackbar = useSnackbar();

function fetchCashAccounts() {
  loadingAccounts.value = true;
  CashAccountService.getActiveCashAccounts()
    .then(res => {
      cashAccountOptions.value = res.data.data;
    })
    .finally(() => loadingAccounts.value = false);
}

function fetchCashMovements() {
  loading.value = true;
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy?.[0]?.key || 'date',
    sort_direction: options.sortBy?.[0]?.order || 'desc',
    ...(filters.cash_account_id ? { cash_account_id: filters.cash_account_id } : {}),
    ...(filters.direction ? { direction: filters.direction } : {}),
    ...(filters.date_from ? { date_from: filters.date_from } : {}),
    ...(filters.date_to ? { date_to: filters.date_to } : {}),
    ...(filters.search ? { search: filters.search } : {}),
    ...(filters.currency ? { currency: filters.currency } : {}),
  };
  CashMovementService.getCashMovements(params)
    .then(res => {
      items.value = res.data.data;
      total.value = res.data.meta?.total || 0;
    })
    .catch(() => {
      items.value = [];
      total.value = 0;
    })
    .finally(() => loading.value = false);
}

watch(() => [filters.cash_account_id, filters.direction, filters.date_from, filters.date_to, filters.search, filters.currency], () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    options.page = 1;
    fetchCashMovements();
  }, 400);
});

watch(options, fetchCashMovements, { deep: true });

onMounted(() => {
  fetchCashAccounts();
  fetchCashMovements();
});

function onOptionsUpdate(newOptions) {
  Object.assign(options, newOptions);
}

function openCreate() {
  editingMovement.value = null;
  openForm.value = true;
}

function onFormCancel() {
  openForm.value = false;
  editingMovement.value = null;
}

async function onFormSubmit(data) {
  formLoading.value = true;
  try {
    await CashMovementService.createCashMovement(data);
    // await CashMovementService.createCashMovement({ ...form });
    snackbar.success('Movimiento registrado correctamente');
    openForm.value = false;
    editingMovement.value = null;
    fetchCashMovements();
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al registrar el movimiento');
  } finally {
    formLoading.value = false;
  }
}

function onDeleteCashMovement(movement) {
  CashMovementService.deleteCashMovement(movement.id)
    .then(() => {
      snackbar.success('Movimiento eliminado correctamente');
      fetchCashMovements();
    })
    .catch(e => {
      snackbar.error(e.response?.data?.message || 'Error al eliminar el movimiento');
    });
}

function onShowCashMovement(movement) {
  // Lógica para ver detalle (a integrar)
}
</script>
