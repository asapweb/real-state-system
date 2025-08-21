<template>
  <div class="d-flex align-center mb-5">
    <v-select
      v-model="searchType"
      :items="voucherTypes"
      item-title="title"
      item-value="value"
      label="Tipo de Comprobante"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 220px"
    ></v-select>
    <v-select
      v-model="searchStatus"
      :items="statusOptions"
      item-title="title"
      item-value="value"
      label="Estado"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 150px"
      class="ml-2"
    ></v-select>
    <v-select
      v-model="searchBooklet"
      :items="booklets"
      item-title="name"
      item-value="id"
      label="Talonario"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 200px"
      class="ml-2"
    ></v-select>
    <v-text-field
      v-model="searchText"
      label="Buscar por cliente, número o concepto"
      prepend-inner-icon="mdi-magnify"
      variant="solo-filled"
      flat
      hide-details
      single-line
      class="ml-2"
    ></v-text-field>
  </div>

  <div>
    <v-data-table-server
      v-model:items-per-page="options.itemsPerPage"
      v-model:sort-by="options.sortBy"
      v-model:page="options.page"
      :headers="headers"
      :items="vouchers"
      :items-length="totalVouchers"
      :loading="loading"
      item-value="id"
      @update:options="fetchVouchers"
    >
      <template #[`item.formatted_number`]="{ item }">
        <RouterLink
          :to="`/vouchers/${item.id}`"
          class="text-primary font-weight-bold text-decoration-none"
        >
          {{ item.full_number }}
        </RouterLink>
      </template>
      <template #item.total="{ item }">
          {{ formatMoney(item.total, item.currency) }}
        </template>
      <template #[`item.client.name`]="{ item }">
        {{ item.client?.name || '—' }}
      </template>
      <template #[`item.status`]="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" class="text-white">
          {{ formatStatus(item.status) }}
        </v-chip>
      </template>
        <template #item.issue_date="{ item }">
          {{ formatDate(item.issue_date) }}
        </template>
        <template #item.due_date="{ item }">
          {{ formatDate(item.due_date) }}
        </template>
      <template #[`item.actions`]="{ item }">
        <v-icon
          v-if="item.status === 'draft'"
          class="me-2"
          size="small"
          @click="issueVoucher(item)"
          title="Emitir Voucher"
          color="success"
        >
          mdi-check-circle
        </v-icon>
        <v-icon class="me-2" size="small" @click="goToEdit(item)" title="Editar Voucher">
          mdi-pencil
        </v-icon>
        <v-icon size="small" @click="openDeleteDialog(item)" title="Eliminar Voucher">
          mdi-delete
        </v-icon>
      </template>
    </v-data-table-server>
  </div>

  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que deseas eliminar el voucher
        <strong>{{ voucherToDelete?.formatted_number }}</strong>? Esta acción no se puede deshacer.
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue-darken-1" variant="text" @click="closeDeleteDialog">Cancelar</v-btn>
        <v-btn color="red-darken-1" variant="text" @click="confirmDelete">Eliminar</v-btn>
        <v-spacer></v-spacer>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import voucherService from '@/services/voucherService'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatMoney } from '@/utils/money'
import { formatDate } from '@/utils/date-formatter'

const emit = defineEmits(['error'])
const router = useRouter()
const snackbar = useSnackbar()

const vouchers = ref([])
const loading = ref(true)
const totalVouchers = ref(0)
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'issue_date', order: 'desc' }],
})
const searchText = ref('')
const searchType = ref(null)
const searchStatus = ref(null)
const searchBooklet = ref(null)
const booklets = ref([])

const dialogDelete = ref(false)
const voucherToDelete = ref(null)

const voucherTypes = [
  // { title: 'Alquiler', value: 'ALQ' },
  // { title: 'Cobranza', value: 'COB' },
  { title: 'Factura', value: 'FAC' },
  { title: 'Nota de Crédito', value: 'N/C' },
  { title: 'Nota de Débito', value: 'N/D' },
  { title: 'Liquidación', value: 'LIQ' },
  { title: 'Recibo de Cobranza', value: 'RCB' },
  { title: 'Recibo de Pago', value: 'RPG' },
]

const statusOptions = [
  { title: 'Borrador', value: 'draft' },
  { title: 'Emitido', value: 'issued' },
  { title: 'Anulado', value: 'canceled' },
]

const headers = [
  { title: 'Comprobante', key: 'formatted_number', sortable: true },
  { title: 'Cliente', key: 'client.name', sortable: false },
  { title: 'Fecha', key: 'issue_date', sortable: true },
  { title: 'Periodo', key: 'period', sortable: true },
  { title: 'Vencimiento', key: 'due_date', sortable: true, align: 'end' },
  { title: 'Total', key: 'total', sortable: true, align: 'end' },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

function statusColor(status) {
  switch (status) {
    case 'draft': return 'grey';
    case 'issued': return 'green';
    case 'canceled': return 'red';
    default: return 'grey';
  }
}
function formatStatus(status) {
  switch (status) {
    case 'draft': return 'Borrador';
    case 'issued': return 'Emitido';
    case 'canceled': return 'Anulado';
    default: return status;
  }
}

const fetchVouchers = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'issue_date',
    sort_direction: options.sortBy[0]?.order || 'desc',
    search: searchText.value,
    voucher_type: searchType.value,
    status: searchStatus.value,
    booklet_id: searchBooklet.value,
  }
  try {
    const { data } = await voucherService.list(params)
    vouchers.value = data.data
    totalVouchers.value = data.meta?.total || data.total || 0
  } catch (error) {
    emit('error', 'No se pudieron cargar los vouchers. Intente nuevamente.')
  } finally {
    loading.value = false
  }
}

const fetchBooklets = async () => {
  try {
    const { data } = await axios.get('/api/booklets')
    booklets.value = data.data || data
  } catch (error) {
    console.error('Error fetching booklets:', error)
  }
}

defineExpose({ reload: fetchVouchers })

function goToEdit(item) {
  router.push({ name: 'VoucherEdit', params: { id: item.id } })
}

function openDeleteDialog(voucher) {
  voucherToDelete.value = voucher
  dialogDelete.value = true
}
function closeDeleteDialog() {
  dialogDelete.value = false
  setTimeout(() => { voucherToDelete.value = null }, 300)
}
async function confirmDelete() {
  if (voucherToDelete.value) {
    try {
      await voucherService.remove(voucherToDelete.value.id)
      snackbar.success('Voucher eliminado correctamente')
      fetchVouchers()
    } catch (error) {
      if (error.response?.status === 422 && error.response?.data?.error) {
        // Error específico de restricción de clave foránea
        snackbar.error(error.response.data.error)
      } else {
        snackbar.error('No se pudo eliminar el voucher')
      }
    }
  }
  closeDeleteDialog()
}

async function issueVoucher(voucher) {
  try {
    await voucherService.issue(voucher.id)
    snackbar.success('Voucher emitido correctamente')
    fetchVouchers() // Recargar la tabla para mostrar el nuevo estado
  } catch (error) {
    console.error('Error issuing voucher:', error)
    if (error.response?.data?.message) {
      snackbar.error(error.response.data.message)
    } else {
      snackbar.error('No se pudo emitir el voucher')
    }
  }
}

onMounted(() => {
  fetchBooklets()
})

let debounceTimer = null
watch(searchText, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    options.page = 1
    fetchVouchers()
  }, 500)
})
watch(searchType, () => {
  options.page = 1
  fetchVouchers()
})
watch(searchStatus, () => {
  options.page = 1
  fetchVouchers()
})
watch(searchBooklet, () => {
  options.page = 1
  fetchVouchers()
})
</script>
