<template>
  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="expenses"
    :items-length="total"
    :loading="loading"
    item-value="id"
    class="elevation-1"
    @update:options="fetchExpenses"
  >
  <!-- Contrato (solo en modo global) -->
    <template v-if="!contractId" #[`item.contract`]="{ item }">
      <RouterLink
        :to="`/contracts/${item.contract.id}`"
        class="text-primary text-decoration-none font-weight-bold"
      >
        {{ formatModelId(item.contract.id, 'CON') }}
      </RouterLink>
      
    </template>
    <!-- Fecha -->
    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date) }}
    </template>

    <!-- Monto -->
    <template #[`item.amount`]="{ item }">
      {{ formatMoney(item.amount, item.currency) }}
    </template>
    
    <!-- Pagado -->
    <template #[`item.is_paid`]="{ item }">
      <v-chip :color="item.is_paid ? 'success' : 'grey'" size="small">
        {{ item.is_paid ? 'Pagado' : 'Pendiente' }}
      </v-chip>
    </template>

    <!-- Estado -->
    <template #[`item.status`]="{ item }">
      <v-chip size="small" :color="statusColor(item.status.value)">
        {{ item.status.label }}
      </v-chip>
    </template>

    <!-- Acciones -->
    <!-- Acciones -->
<template #[`item.actions`]="{ item }">
  <div class="d-flex align-center">
    <!-- Editar -->
    <v-icon size="small" class="me-2" @click="$emit('edit-expense', item)">
      mdi-pencil
    </v-icon>

    <!-- Eliminar -->
    <v-icon
      size="small"
      class="me-2"
      color="error"
      @click="$emit('delete-expense', item)"
      :disabled="item.is_locked"
    >
      mdi-delete
    </v-icon>

    <!-- Menú contextual -->
    <v-menu>
      <template #activator="{ props }">
        <v-btn icon variant="text" size="small" v-bind="props">
          <v-icon>mdi-dots-vertical</v-icon>
        </v-btn>
      </template>
      <v-list>
        <!-- Registrar pago -->
        <v-list-item
          v-if="canRegisterPayment(item)"
          @click="openPaymentModal(item)"
        >
          <v-list-item-title>
            <v-icon size="small" class="me-2">mdi-cash-check</v-icon>
            Registrar pago
          </v-list-item-title>
        </v-list-item>

        <!-- Facturar -->
        <v-list-item
          v-if="canBill(item)"
          @click="changeStatus(item, 'billed')"
        >
          <v-list-item-title>
            <v-icon size="small" class="me-2">mdi-file-document-edit</v-icon>
            Facturar
          </v-list-item-title>
        </v-list-item>

        <!-- Generar NC -->
        <v-list-item
          v-if="canCredit(item)"
          @click="changeStatus(item, 'credited')"
        >
          <v-list-item-title>
            <v-icon size="small" class="me-2">mdi-file-document-minus</v-icon>
            Generar NC
          </v-list-item-title>
        </v-list-item>

        <!-- Liquidar -->
        <v-list-item
          v-if="canLiquidate(item)"
          @click="changeStatus(item, 'liquidated')"
        >
          <v-list-item-title>
            <v-icon size="small" class="me-2">mdi-file-document-check</v-icon>
            Liquidar
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Adjuntos -->
    <v-icon
      size="small"
      color="primary"
      class="ms-2"
      title="Ver adjuntos"
      @click="openAttachments(item)"
    >
      mdi-paperclip
    </v-icon>
  </div>
</template>

  </v-data-table-server>

  <!-- Modal de adjuntos -->
  <v-dialog v-model="showAttachmentsDialog" max-width="800px">
    <v-card>
      <v-card-title class="text-h6">
        Archivos del gasto #{{ selectedExpense?.id }}
        <v-spacer />
        <v-btn icon variant="text" @click="showAttachmentsDialog = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <AttachmentUploader
          v-if="selectedExpense"
          :expense-id="selectedExpense.id"
        />
      </v-card-text>
    </v-card>
  </v-dialog>

  <!-- Modal de registrar pago -->
  <ContractExpensePaymentModal
    v-model="showPaymentModal"
    :expense="selectedExpense"
    @saved="fetchExpenses"
  />
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '@/services/axios'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatStatus, statusColor } from '@/utils/collections-formatter'
import AttachmentUploader from './AttachmentUploader.vue'
import ContractExpensePaymentModal from './ContractExpensePaymentModal.vue'
import { formatModelId } from '@/utils/models-formatter'


const props = defineProps({
  contractId: { type: Number, required: false, default: null },
})

const emit = defineEmits(['edit-expense', 'delete-expense'])
const snackbar = useSnackbar()

const expenses = ref([])
const total = ref(0)
const loading = ref(false)
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'desc' }],
})

// Modal de adjuntos
const showAttachmentsDialog = ref(false)
const selectedExpense = ref(null)

// Modal de registrar pago
const showPaymentModal = ref(false)

const headers = computed(() => {
  const base = [
    ...(props.contractId ? [] : [{ title: 'Contrato', key: 'contract', sortable: false }]),
    { title: 'Fecha', key: 'effective_date', sortable: true },
    { title: 'Servicio', key: 'service_type.name', sortable: false },
    { title: 'Monto', key: 'amount', sortable: true },
    { title: 'Pagado', key: 'is_paid', sortable: false },
    { title: 'Pagado por', key: 'paid_by', sortable: false },
    { title: 'Responsable', key: 'responsible_party', sortable: false },
    { title: 'Estado', key: 'status', sortable: false },
    { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
  ]
  return base
})

const canRegisterPayment = (expense) =>
  expense.status.value === 'pending' &&
  expense.paid_by === 'tenant' &&
  expense.responsible_party === 'tenant'

const canBill = (expense) =>
  ['pending', 'validated'].includes(expense.status.value) &&
  expense.responsible_party === 'tenant' &&
  expense.paid_by !== 'tenant'

const canCredit = (expense) =>
  expense.status.value === 'pending' &&
  expense.paid_by === 'tenant' &&
  expense.responsible_party === 'owner'

const canLiquidate = (expense) =>
  (expense.status.value === 'credited' && expense.paid_by === 'tenant' && expense.responsible_party === 'owner') ||
  (expense.status.value === 'pending' && expense.paid_by === 'agency' && expense.responsible_party === 'owner') ||
  (expense.status.value === 'pending' && expense.paid_by === 'owner' && expense.responsible_party === 'owner')

const changeStatus = async (expense, newStatus) => {
  try {
    await axios.post(`/api/contract-expenses/${expense.id}/change-status`, {
      status: newStatus,
    })
    snackbar.success('Estado actualizado correctamente.')
    fetchExpenses()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al cambiar el estado.')
  }
}

const fetchExpenses = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/contract-expenses', {
      params: {
        contract_id: props.contractId,
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
      },
    })
    expenses.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar los gastos')
  } finally {
    loading.value = false
  }
}

const openAttachments = (expense) => {
  selectedExpense.value = expense
  showAttachmentsDialog.value = true
}

const openPaymentModal = (expense) => {
  selectedExpense.value = expense
  showPaymentModal.value = true
}

onMounted(fetchExpenses)
defineExpose({ fetchExpenses })
</script>
