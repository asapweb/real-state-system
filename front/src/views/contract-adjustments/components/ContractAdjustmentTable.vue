<template>
  <div class="d-flex align-center mb-5">
    <v-select
      v-model="filters.status"
      :items="statusOptions"
      item-title="title"
      item-value="value"
      label="Estado"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 220px"
    />
    <v-select
      v-model="filters.type"
      :items="typeOptions"
      item-title="title"
      item-value="value"
      label="Tipo"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 180px"
      class="ml-2"
    />
    <v-select
      v-model="filters.contract_id"
      :items="contractOptions"
      item-title="title"
      item-value="value"
      label="Contrato"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 200px"
      class="ml-2"
    />
    <v-select
      v-model="filters.client_id"
      :items="clientOptions"
      item-title="title"
      item-value="value"
      label="Cliente"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 200px"
      class="ml-2"
    />
    <v-text-field
      v-model="filters.effective_date_from"
      label="Fecha desde"
      type="date"
      variant="solo-filled"
      flat
      hide-details
      style="max-width: 160px"
      class="ml-2"
    />
    <v-text-field
      v-model="filters.effective_date_to"
      label="Fecha hasta"
      type="date"
      variant="solo-filled"
      flat
      hide-details
      style="max-width: 160px"
      class="ml-2"
    />
  </div>

  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="adjustments"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchAdjustments"
  >
    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date) }}
    </template>

    <template #[`item.contract`]="{ item }">
      <div v-if="item.contract" >
        <RouterLink
            :to="`/contracts/${item.contract.id}`"
            class="text-primary text-decoration-none font-weight-bold"
          >
            {{ formatModelId(item.contract.id, 'CON') }}
          </RouterLink>

          <div v-if="item.contract.clients && item.contract.clients.length > 0" class="text-caption text-grey">
            {{ item.contract.clients.map(c => c.client.name + ' ' + c.client.last_name).join(', ') }}
          </div>
      </div>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.type`]="{ item }">
      {{ adjustmentLabel(item.type) }}
    </template>

    <template #[`item.index_type`]="{ item }">
      <span v-if="item.type === 'index' && item.index_type">
        {{ item.index_type.name }}
      </span>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.value`]="{ item }">
      <template v-if="item.type === 'percentage'">
        {{ item.value !== null ? `${item.value}%` : '—' }}
      </template>
      <template v-else-if="item.type === 'fixed' || item.type === 'negotiated'">
        {{ item.value !== null ? formatMoney(item.value, item.contract?.currency || '$') : '—' }}
      </template>
      <template v-else-if="item.type === 'index'">
        <span v-if="item.value !== null">{{ item.value }}%</span>
        <span v-else class="text-grey">Pendiente</span>
      </template>
      <template v-else> — </template>
    </template>

    <template #[`item.applied_amount`]="{ item }">
      <template v-if="item.applied_amount !== null">
        {{ formatMoney(item.applied_amount, item.contract?.currency || '$') }}
      </template>
      <template v-else>
        <span class="text-grey">—</span>
      </template>
    </template>

    <template #[`item.applied_at`]="{ item }">
      <div class="d-flex align-center">
        <template v-if="item.applied_at">
          <v-icon color="success" size="small" class="me-1">mdi-check-circle</v-icon>
          {{ formatDate(item.applied_at) }}
        </template>
        <template
          v-else-if="item.value !== null && new Date(item.effective_date) <= new Date()"
        >
          <v-icon color="warning" size="small" class="me-1">mdi-clock-alert</v-icon>
          <span class="text-warning">Pendiente</span>
        </template>
        <template v-else>
          <span class="text-grey">—</span>
        </template>
      </div>
    </template>

    <template #[`item.contract_start_date`]="{ item }">
      <span v-if="item.contract?.start_date">
        {{ formatDate(item.contract.start_date) }}
      </span>
      <span v-else class="text-grey text-caption">—</span>
    </template>

    <template #[`item.contract_monthly_amount`]="{ item }">
      <span v-if="item.contract?.monthly_amount">
        {{ formatMoney(item.contract.monthly_amount, item.contract.currency || '$') }}
      </span>
      <span v-else class="text-grey text-caption">—</span>
    </template>

    <template #[`item.notes`]="{ item }">
      <span v-if="item.notes" class="text-truncate" style="max-width: 200px;">
        {{ item.notes }}
      </span>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.status`]="{ item }">
      <v-chip :color="statusColor(getStatus(item))" size="small" class="text-capitalize">
        {{ formatStatus(getStatus(item)) }}
      </v-chip>
    </template>

    <template #[`item.actions`]="{ item }">
      <div class="d-flex align-center">
        <!-- Botón Aplicar (solo si está pendiente) -->
        <v-btn
          v-if="item.value !== null && item.applied_at === null"
          size="small"
          color="success"
          variant="text"
          :loading="applyingAdjustment === item.id"
          :disabled="applyingAdjustment !== null"
          @click="applyAdjustment(item)"
          class="me-2"
        >
          <v-icon size="small" class="me-1">mdi-check</v-icon>
          Aplicar
        </v-btn>

        <!-- Badge de aplicado -->
        <v-chip
          v-else-if="item.applied_at !== null"
          size="small"
          color="success"
          variant="flat"
          class="me-2"
        >
          <v-icon size="small" class="me-1">mdi-check-circle</v-icon>
          Aplicado
        </v-chip>

        <!-- Acciones existentes -->
        <v-icon
          v-if="item.applied_at === null"
          class="me-2"
          size="small"
          @click="openEditDialog(item)"
          title="Editar ajuste"
          :disabled="applyingAdjustment !== null"
        >
          mdi-pencil
        </v-icon>
        <v-icon
          v-if="item.applied_at === null"
          size="small"
          @click="confirmDelete(item)"
          title="Eliminar ajuste"
          :disabled="applyingAdjustment !== null"
        >
          mdi-delete
        </v-icon>
      </div>
    </template>
  </v-data-table-server>

  <!-- Formulario embebido -->
  <v-dialog v-model="dialog" max-width="600px">
    <ContractAdjustmentForm
      :initial-data="selectedAdjustment"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Confirmación de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text> ¿Estás seguro de que querés eliminar este ajuste? </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteAdjustment">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, reactive, onMounted } from 'vue'
import axios from '@/services/axios'
import { formatModelId } from '@/utils/models-formatter'
import { formatStatus, statusColor } from '@/utils/collections-formatter'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import ContractAdjustmentForm from '../../contracts/components/ContractAdjustmentForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const emit = defineEmits(['view-adjustment', 'edit-adjustment', 'delete-adjustment', 'error'])
const snackbar = useSnackbar()

const adjustments = ref([])
const loading = ref(true)
const total = ref(0)
const saving = ref(false)
const applyingAdjustment = ref(null)
const dialog = ref(false)
const dialogDelete = ref(false)
const selectedAdjustment = ref(null)
const adjustmentToDelete = ref(null)
const contractOptions = ref([])
const clientOptions = ref([])

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'asc' }],
})

const filters = reactive({
  status: null,
  type: null,
  contract_id: null,
  client_id: null,
  effective_date_from: null,
  effective_date_to: null,
})

const headers = [
  { title: 'Contrato', key: 'contract', sortable: false },
  { title: 'Tipo', key: 'type', sortable: false },
  { title: 'Índice', key: 'index_type', sortable: false },
  { title: 'Inicio ', key: 'contract_start_date', sortable: false },
  { title: 'Ajuste', key: 'effective_date', sortable: true },
  { title: 'Valor', key: 'value', sortable: true },
  { title: 'Base', key: 'contract_monthly_amount', sortable: false },
  { title: 'Nuevo', key: 'applied_amount', sortable: true },
  { title: 'Aplicado', key: 'applied_at', sortable: true },
  { title: 'Notas', key: 'notes', sortable: false },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const statusOptions = [
  { title: 'Pendiente', value: 'pending' },
  { title: 'Vencido sin valor', value: 'expired_without_value' },
  { title: 'Con valor', value: 'with_value' },
  { title: 'Aplicado', value: 'applied' },
]

const typeOptions = [
  { title: 'Fijo', value: 'fixed' },
  { title: 'Porcentaje', value: 'percentage' },
  { title: 'Índice', value: 'index' },
  { title: 'Negociado', value: 'negotiated' },
]

const adjustmentLabel = (type) => {
  const map = {
    fixed: 'Monto fijo',
    percentage: 'Porcentaje',
    index: 'Índice',
    negotiated: 'Negociado',
  }
  return map[type] || type
}

function getStatus(a) {
  const today = new Date().toISOString().split('T')[0]
  if (a.applied_at) return 'applied'
  if (a.value !== null && a.value !== undefined) {
    return a.effective_date <= today ? 'with_value' : 'pending'
  }
  return a.effective_date <= today ? 'expired_without_value' : 'pending'
}

const fetchContracts = async () => {
  try {
    const { data } = await axios.get('/api/contracts', { params: { per_page: 100 } })
    contractOptions.value = data.data.map(contract => ({
      title: `Contrato ${contract.id}`,
      value: contract.id
    }))
  } catch (e) {
    console.error('Error cargando contratos:', e)
  }
}

const fetchClients = async () => {
  try {
    const { data } = await axios.get('/api/clients', { params: { per_page: 100 } })
    clientOptions.value = data.data.map(client => ({
      title: `${client.name} ${client.last_name}`,
      value: client.id
    }))
  } catch (e) {
    console.error('Error cargando clientes:', e)
  }
}

const fetchAdjustments = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key,
    sort_direction: options.sortBy[0]?.order,
    ...filters,
  }
  try {
    const { data } = await axios.get('api/contract-adjustments/global', { params })
    adjustments.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar ajustes de contratos')
  } finally {
    loading.value = false
  }
}

const openEditDialog = (item) => {
  selectedAdjustment.value = item
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedAdjustment.value = null
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (formData.id) {
      await axios.put(`/api/contracts/${formData.contract_id}/adjustments/${formData.id}`, formData)
    } else {
      await axios.post(`/api/contracts/${formData.contract_id}/adjustments`, formData)
    }
    snackbar.success('Ajuste guardado correctamente')
    fetchAdjustments()
    dialog.value = false
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al guardar ajuste'
    snackbar.error(errorMessage)
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  adjustmentToDelete.value = item
  dialogDelete.value = true
}

const deleteAdjustment = async () => {
  try {
    await axios.delete(
      `/api/contracts/${adjustmentToDelete.value.contract_id}/adjustments/${adjustmentToDelete.value.id}`,
    )
    snackbar.success('Ajuste eliminado')
    fetchAdjustments()
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al eliminar ajuste'
    snackbar.error(errorMessage)
  } finally {
    dialogDelete.value = false
    adjustmentToDelete.value = null
  }
}

const applyAdjustment = async (item) => {
  applyingAdjustment.value = item.id
  try {
    await axios.post(`/api/contracts/${item.contract_id}/adjustments/${item.id}/apply`)
    snackbar.success('Ajuste aplicado correctamente')
    fetchAdjustments()
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al aplicar ajuste'
    snackbar.error(errorMessage)
  } finally {
    applyingAdjustment.value = null
  }
}

watch(
  filters,
  () => {
    options.page = 1
    fetchAdjustments()
  },
  { deep: true },
)

onMounted(() => {
  fetchContracts()
  fetchClients()
  fetchAdjustments()
})

defineExpose({ reload: fetchAdjustments })
</script>
