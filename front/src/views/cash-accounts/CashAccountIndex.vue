<template>
  <v-container fluid>
    <v-row align="center" justify="space-between" class="mb-4">
      <v-col cols="12" md="6">
        <h1 class="text-h5 font-weight-bold">Cajas</h1>
      </v-col>
      <v-col cols="12" md="6" class="text-end">
        <v-btn color="primary" @click="openCreate">
          + Nueva caja
        </v-btn>
      </v-col>
    </v-row>

    <v-row class="mb-2" align="center">
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filters.name"
          label="Buscar por nombre"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.type"
          :items="typeOptions"
          label="Tipo"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.is_active"
          :items="statusOptions"
          label="Estado"
          clearable
          density="compact"
          hide-details
        />
      </v-col>
    </v-row>

    <cash-account-table
      :items="items"
      :loading="loading"
      :total="total"
      :options="options"
      @update:options="onOptionsUpdate"
      @edit-cash-account="onEditCashAccount"
      @delete-cash-account="onDeleteCashAccount"
    />

    <!-- Modal para alta/edición de caja -->
    <v-dialog v-model="openForm" max-width="500">
      <cash-account-form
        v-if="openForm"
        :initial-data="editingAccount"
        :loading="formLoading"
        @submit="onFormSubmit"
        @cancel="onFormCancel"
      />
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import CashAccountTable from './components/CashAccountTable.vue';
import CashAccountForm from './components/CashAccountForm.vue';
import CashAccountService from '@/services/CashAccountService';
import { useSnackbar } from '@/composables/useSnackbar';

const items = ref([]);
const total = ref(0);
const loading = ref(false);
const openForm = ref(false);
const formLoading = ref(false);
const editingAccount = ref(null);
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'name', order: 'asc' }],
});

const filters = reactive({
  name: '',
  type: null,
  is_active: null,
});

const typeOptions = [
  { title: 'Caja', value: 'cash' },
  { title: 'Banco', value: 'bank' },
  { title: 'Virtual', value: 'virtual' },
];
const statusOptions = [
  { title: 'Activa', value: true },
  { title: 'Inactiva', value: false },
];

let debounceTimeout;
const snackbar = useSnackbar();

function fetchCashAccounts() {
  loading.value = true;
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy?.[0]?.key || 'name',
    sort_direction: options.sortBy?.[0]?.order || 'asc',
    ...(filters.name ? { name: filters.name } : {}),
    ...(filters.type ? { type: filters.type } : {}),
    ...(filters.is_active !== null ? { is_active: filters.is_active } : {}),
  };
  CashAccountService.getCashAccounts(params)
    .then(res => {
      items.value = res.data.data;
      total.value = res.data.meta?.total || 0;
    })
    .finally(() => loading.value = false);
}

watch(() => [filters.name, filters.type, filters.is_active], ([name]) => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    options.page = 1;
    fetchCashAccounts();
  }, name !== undefined ? 400 : 0);
});

watch(options, fetchCashAccounts, { deep: true });

onMounted(fetchCashAccounts);

function onOptionsUpdate(newOptions) {
  Object.assign(options, newOptions);
}

function openCreate() {
  editingAccount.value = null;
  openForm.value = true;
}

function onEditCashAccount(account) {
  editingAccount.value = { ...account };
  openForm.value = true;
}

function onFormCancel() {
  openForm.value = false;
  editingAccount.value = null;
}

async function onFormSubmit(data) {
  formLoading.value = true;
  try {
    if (editingAccount.value && editingAccount.value.id) {
      await CashAccountService.updateCashAccount(editingAccount.value.id, data);
      snackbar.success('Caja actualizada correctamente');
    } else {
      await CashAccountService.createCashAccount(data);
      snackbar.success('Caja creada correctamente');
    }
    openForm.value = false;
    editingAccount.value = null;
    fetchCashAccounts();
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al guardar la caja');
  } finally {
    formLoading.value = false;
  }
}

function onDeleteCashAccount(account) {
  // Aquí podrías abrir un diálogo de confirmación antes de desactivar
  CashAccountService.deleteCashAccount(account.id)
    .then(() => {
      snackbar.success('Caja desactivada correctamente');
      fetchCashAccounts();
    })
    .catch(e => {
      snackbar.error(e.response?.data?.message || 'Error al desactivar la caja');
    });
}
</script>
