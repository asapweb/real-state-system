<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Gastos</span>
      <v-spacer></v-spacer>
      <v-btn
        v-if="editable"
        color="primary"
        variant="text"
        @click="openCreateDialog"
        prepend-icon="mdi-plus"
      >
        Agregar gasto
      </v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:sort-by="options.sortBy"
        v-model:page="options.page"
        :headers="headers"
        :items="expenses"
        :items-length="total"
        :loading="loading"
        @update:options="fetchExpenses"
      >
        <template #[`item.period`]="{ item }">
          {{ item.period?.slice(0, 7) }}
        </template>

        <template #[`item.amount`]="{ item }">
          {{ formatMoney(item.amount, item.currency) }}
        </template>

        <template #[`item.is_paid`]="{ item }">
          <v-chip :color="item.is_paid ? 'success' : 'warning'" variant="flat">
            {{ item.is_paid ? 'Pagado' : 'Impago' }}
          </v-chip>
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="editExpense(item)" title="Editar">
            mdi-pencil
          </v-icon>
          <v-icon size="small" @click="confirmDelete(item)" title="Eliminar"> mdi-delete </v-icon>
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>

  <v-dialog v-model="dialog" max-width="700px">
    <ContractExpenseForm
      :initial-data="selectedExpense"
      :loading="creating"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <ConfirmDialog
    v-model="deleteDialog"
    :message="`¿Estás seguro de eliminar el gasto del mes <strong>${expenseToDelete?.period?.slice(0, 7)}</strong>?`"
    title="Confirmar eliminación"
    confirm-text="Eliminar"
    confirm-color="error"
    :loading="deleting"
    @confirm="deleteExpense"
    @cancel="deleteDialog = false"
  />
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractExpenseForm from './ContractExpenseForm.vue'
import ConfirmDialog from '@/views/components/ConfirmDialog.vue'
import { formatMoney } from '@/utils/money'

const props = defineProps({
  contractId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})
const emit = defineEmits(['updated'])

const expenses = ref([])
const total = ref(0)
const loading = ref(false)
const creating = ref(false)
const dialog = ref(false)
const selectedExpense = ref(null)

const snackbar = useSnackbar()

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'period', order: 'desc' }],
})

const headers = [
  { title: 'Mes', key: 'period' },
  { title: 'Servicio', key: 'service_type' },
  { title: 'Pagado por', key: 'paid_by' },
  { title: 'Importe', key: 'amount' },
  { title: 'Estado', key: 'is_paid' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const deleteDialog = ref(false)
const deleting = ref(false)
const expenseToDelete = ref(null)

const openCreateDialog = () => {
  selectedExpense.value = null
  dialog.value = true
}

const closeDialog = () => {
  selectedExpense.value = null
  dialog.value = false
}

const editExpense = (item) => {
  selectedExpense.value = { ...item }
  dialog.value = true
}

const confirmDelete = (item) => {
  expenseToDelete.value = item
  deleteDialog.value = true
}

const deleteExpense = async () => {
  if (!expenseToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/contracts/${props.contractId}/expenses/${expenseToDelete.value.id}`)
    snackbar.success('Gasto eliminado correctamente')
    fetchExpenses()
    emit('updated')
  } catch (error) {
    snackbar.error('Error al eliminar el gasto')
    console.error(error)
  } finally {
    deleting.value = false
    deleteDialog.value = false
  }
}

const handleSave = async (formData) => {
  creating.value = true
  try {
    if (formData.id) {
      await axios.put(`/api/contracts/${props.contractId}/expenses/${formData.id}`, {
        ...formData,
        contract_id: props.contractId,
      })
    } else {
      await axios.post(`/api/contracts/${props.contractId}/expenses`, {
        ...formData,
        contract_id: props.contractId,
      })
    }
    snackbar.success('Gasto guardado correctamente')
    dialog.value = false
    fetchExpenses()
    emit('updated')
  } catch (error) {
    let msg = 'Error al guardar el gasto'
    if (error.response?.data?.message) msg = error.response.data.message
    snackbar.error(msg)
    console.error(error)
  } finally {
    creating.value = false
  }
}

const fetchExpenses = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contracts/${props.contractId}/expenses`, {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
      },
    })
    expenses.value = data.data
    total.value = data.total
  } catch (error) {
    snackbar.error('Error al cargar gastos')
    console.error(error)
  } finally {
    loading.value = false
  }
}

watch(() => props.contractId, fetchExpenses, { immediate: true })
</script>
