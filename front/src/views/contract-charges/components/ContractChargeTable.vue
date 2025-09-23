<template>
  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="charges"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchCharges"
  >
    <template v-if="!contractId" #[`item.contract`]="{ item }">
      <div v-if="item.contract" class="d-flex flex-column">
          <span class="font-weight-medium">{{ item.contract.main_tenant.client.full_name}}</span>
          <small class="text-medium-emphasis"><RouterLink :to="`/contracts/${item.contract.id}`" class="text-primary text-decoration-none">
          {{ formatModelId(item.contract.id, 'CON') }}
            <!-- {{ item.contract.id }} -->
        </RouterLink> ({{ formatDate(item.contract?.start_date) }})</small>
      </div>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.effective_date`]="{ item }">
      {{ formatDate(item.effective_date) }}
    </template>

    <template #[`item.due_date`]="{ item }">
      {{ formatDate(item.due_date) || '—' }}
    </template>

    <template #[`item.charge_type`]="{ item }">
      <div class="text-medium-emphasis">
        <small>{{ item.charge_type?.name || item.charge_type?.code || '-' }}</small>
      </div>
      <span class="text-truncate" :title="item.description">
        {{ item.description || '—' }}
      </span>
    </template>

    <template #[`item.counterparty`]="{ item }">
      {{ counterpartyName(item) }}
    </template>

    <template #[`item.service_period`]="{ item }">
      <span v-if="item.service_period_start || item.service_period_end">
        {{ formatServicePeriod(item.service_period_start, item.service_period_end) }}
      </span>
      <span v-else>—</span>
    </template>

    <template #[`item.amount`]="{ item }">
      {{ formatMoney(item.amount, item.currency) }}
    </template>

    <template #[`item.vouchers`]="{ item }">
      <div class="d-flex flex-column gap-1">
        <RouterLink
          v-if="item.voucher_id"
          :to="{ name: 'VoucherShow', params: { id: item.voucher_id } }"
          class="text-primary text-decoration-none"
        >
          COB {{ formatModelId(item.voucher_id, 'VOU') }}
        </RouterLink>
        <RouterLink
          v-if="item.liquidation_voucher_id"
          :to="{ name: 'VoucherShow', params: { id: item.liquidation_voucher_id } }"
          class="text-primary text-decoration-none"
        >
          LIQ {{ formatModelId(item.liquidation_voucher_id, 'VOU') }}
        </RouterLink>
        <span v-if="!item.voucher_id && !item.liquidation_voucher_id">—</span>
      </div>
    </template>

    <template #[`item.description`]="{ item }">
      <span class="text-truncate" :title="item.description">
        {{ item.description || '—' }}
      </span>
    </template>

    <template #[`item.actions`]="{ item }">
      <div class="d-flex align-center justify-end">
        <v-btn
          icon
          size="small"
          variant="text"
          :to="{ name: 'ContractChargeEdit', params: { id: item.id } }"
          :title="`Editar #${item.id}`"
        >
          <v-icon size="20">mdi-pencil</v-icon>
        </v-btn>
      </div>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import { formatModelId } from '@/utils/models-formatter'

const props = defineProps({
  contractId: { type: Number, required: false, default: null },
  filters: { type: Object, required: false, default: null },
})

const snackbar = useSnackbar()

const charges = ref([])
const total = ref(0)
const loading = ref(false)
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'desc' }],
})

const headers = computed(() => {
  return [
    ...(props.contractId ? [] : [{ title: 'Contrato / Inquilino', key: 'contract', sortable: false },]),
    { title: 'Fecha efectiva', key: 'effective_date', sortable: true },
    { title: 'Vencimiento', key: 'due_date', sortable: true },
    { title: 'Tipo de cargo', key: 'charge_type', sortable: false },
    { title: 'Contraparte', key: 'counterparty', sortable: false },
    { title: 'Período servicio', key: 'service_period', sortable: false },
    { title: 'Monto', key: 'amount', sortable: true },
    { title: 'Vouchers', key: 'vouchers', sortable: false },
    { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
  ]
})

const formatServicePeriod = (start, end) => {
  const from = formatDate(start)
  const to = formatDate(end)
  if (from && to) return `${from} → ${to}`
  return from || to || '—'
}

const counterpartyName = (item) => {
  if (!item?.counterparty) return '—'
  const counterparty = item.counterparty
  const client = counterparty.client || {}
  const nameParts = [client.last_name, client.name].filter(Boolean)
  if (nameParts.length) {
    return nameParts.join(', ')
  }
  if (client.company_name) return client.company_name
  if (counterparty.role) {
    const role = counterparty.role.toString().toLowerCase()
    const roleMap = { tenant: 'Inquilino', owner: 'Propietario', guarantor: 'Garante' }
    return roleMap[role] || role
  }
  return `#${counterparty.id}`
}

const fetchCharges = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/contract-charges', {
      params: {
        contract_id: (props.contractId ?? props?.filters?.contract_id) || undefined,
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
        type_code: props.filters?.type_code || undefined,
        period: props.filters?.period || undefined,
      },
    })
    charges.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar los cargos')
  } finally {
    loading.value = false
  }
}

onMounted(fetchCharges)
defineExpose({ fetchCharges })

watch(
  () => props.filters,
  () => {
    options.page = 1
    fetchCharges()
  },
  { deep: true }
)
</script>

<style scoped>
.text-truncate {
  display: inline-block;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
