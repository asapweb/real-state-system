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
    <template #[`item.id`]="{ item }">
      <RouterLink
        :to="`/contracts/${item.contract_id}`"
        class="text-primary font-weight-bold text-decoration-none"
      >
        {{ formatModelId(item.id, 'ADJ') }}
      </RouterLink>
    </template>

    <template #[`item.type`]="{ item }">
      <span>
        {{ item.type }}
        <template v-if="item.type === 'index' && item.index_type">
          / {{ item.index_type.name }}
        </template>
      </span>
    </template>

    <template #[`item.contract_code`]="{ item }">
      <RouterLink
        :to="`/contracts/${item.contract_id}`"
        class="text-primary font-weight-bold text-decoration-none"
      >
        {{ getContractCode(item) }}
      </RouterLink>
    </template>

    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date) }}
    </template>

    <template #[`item.client_name`]="{ item }">
      {{ getTenantName(item) }}
    </template>

    <template #[`item.status`]="{ item }">
      <v-chip :color="statusColor(getStatus(item))" size="small" class="text-capitalize">
        {{ formatStatus(getStatus(item)) }}
      </v-chip>
    </template>

    <template #[`item.actions`]="{ item }">
      <v-icon size="small" class="me-2" @click="emit('edit-adjustment', item)" title="Editar valor">
        mdi-pencil
      </v-icon>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, watch, reactive } from 'vue'
import axios from '@/services/axios'
import { formatModelId } from '@/utils/models-formatter'
import { formatStatus, statusColor } from '@/utils/collections-formatter'
import { formatDate } from '@/utils/date-formatter'
const emit = defineEmits(['view-adjustment', 'edit-adjustment', 'delete-adjustment', 'error'])

const adjustments = ref([])
const loading = ref(true)
const total = ref(0)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'asc' }],
})

const filters = reactive({
  status: null,
  type: null,
  effective_date_from: null,
  effective_date_to: null,
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Contrato', key: 'contract_code' },
  { title: 'Cliente', key: 'client_name' },
  { title: 'Tipo', key: 'type' },
  { title: 'Fecha', key: 'effective_date' },
  { title: 'Valor', key: 'value' },
  { title: 'Estado', key: 'status' },
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

function getStatus(a) {
  const today = new Date().toISOString().split('T')[0]
  if (a.applied_at) return 'applied'
  if (a.value !== null && a.value !== undefined) {
    return a.effective_date <= today ? 'with_value' : 'pending'
  }
  return a.effective_date <= today ? 'expired_without_value' : 'pending'
}

function getContractCode(item) {
  return formatModelId(item.contract?.id) ?? '—'
}

function getTenantName(item) {
  const tenant = item.contract?.clients?.find((c) => c.role === 'tenant')?.client
  return tenant ? `${tenant.name} ${tenant.last_name}` : '—'
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
    emit('error', 'Error al cargar ajustes de contratos')
    console.error(e)
  } finally {
    loading.value = false
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

defineExpose({ reload: fetchAdjustments })

fetchAdjustments()
</script>
