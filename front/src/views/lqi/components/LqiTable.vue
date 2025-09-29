<template>
  <div>
    <v-data-table-server
      v-model:items-per-page="options.itemsPerPage"
      v-model:sort-by="options.sortBy"
      v-model:page="options.page"
      :headers="headers"
      :items="liquidations"
      :items-length="total"
      :loading="loading"
      item-value="id"
      class="elevation-1"
      @update:options="fetchRows"
    >
      <template #[`item.contract`]="{ item }">
        <div class="d-flex flex-column">
          <span class="font-weight-medium">{{ contractCode(item) }}</span>
          <small class="text-medium-emphasis">
            {{ contractTenant(item) }}
          </small>
        </div>
      </template>

      <template #[`item.period`]="{ item }">
        {{ formatPeriod(item.period) }}
      </template>

      <template #[`item.currency`]="{ item }">
        {{ item.currency || '—' }}
      </template>

      <template #[`item.status`]="{ item }">
        <v-chip
          size="small"
          :color="statusColor(item.status)"
          variant="tonal"
        >
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>

      <template #[`item.total`]="{ item }">
        {{ formatMoney(item.total, item.currency) }}
      </template>

      <template #[`item.actions`]="{ item }">
        <div class="d-flex align-center justify-end flex-wrap" style="gap: 6px;">
          <v-btn
            v-if="item.status === 'draft'"
            size="small"
            color="primary"
            variant="text"
            :loading="isActionLoading(item.id, 'sync')"
            @click="handleSync(item)"
          >
            Sincronizar
          </v-btn>

          <v-btn
            v-if="item.status === 'draft'"
            size="small"
            color="success"
            variant="flat"
            :loading="isActionLoading(item.id, 'issue')"
            :disabled="!canIssue(item)"
            @click="handleIssue(item)"
          >
            Emitir
          </v-btn>

          <v-btn
            v-if="item.status === 'issued'"
            size="small"
            color="warning"
            variant="outlined"
            :loading="isActionLoading(item.id, 'reopen')"
            @click="openReopenDialog(item)"
          >
            Reabrir
          </v-btn>
        </div>
      </template>
    </v-data-table-server>

    <v-dialog v-model="reopenDialog" max-width="520">
      <v-card>
        <v-card-title class="text-h6">Reabrir liquidación</v-card-title>
        <v-card-text>
          <p class="text-body-2 mb-4">
            Confirmá la reapertura de la liquidación
            <strong>{{ reopenSummary }}</strong>.
          </p>
          <v-textarea
            v-model="reopenReason"
            label="Motivo de reapertura"
            auto-grow
            rows="2"
            variant="solo-filled"
            :error-messages="reopenReasonErrors"
            :disabled="reopening"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="reopening" @click="closeReopenDialog">Cancelar</v-btn>
          <v-btn color="warning" :loading="reopening" @click="confirmReopen">
            Reabrir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatMoney } from '@/utils/money'
import { formatModelId } from '@/utils/models-formatter'
import {
  fetchLiquidations as fetchLiquidationsRequest,
  syncLiquidations,
  issueLiquidations,
  reopenLiquidations,
} from '@/services/lqiService'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
})

const snackbar = useSnackbar()

const liquidations = ref([])
const total = ref(0)
const loading = ref(false)
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'period', order: 'desc' }],
})

const headers = [
  { title: 'Contrato', key: 'contract', sortable: false },
  { title: 'Período', key: 'period', sortable: true },
  { title: 'Moneda', key: 'currency', sortable: true },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Total', key: 'total', sortable: true, align: 'end' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const rowLoading = reactive({})
const reopenDialog = ref(false)
const reopenReason = ref('')
const reopenReasonErrors = ref([])
const reopening = ref(false)
const selectedLqi = ref(null)

const reopenSummary = computed(() => {
  if (!selectedLqi.value) return ''
  const code = contractCode(selectedLqi.value)
  const period = formatPeriod(selectedLqi.value.period)
  return `${code} · ${period}`
})

function contractCode(item) {
  return formatModelId(item.contract_id, 'CON')
}

function contractTenant(item) {
  return item.client_name
    || item.client?.name
    || item.client?.full_name
    || item.contract?.main_tenant?.client?.full_name
    || '—'
}

function periodForPayload(period) {
  if (!period) return null
  if (/^\d{4}-\d{2}$/.test(period)) return period
  if (typeof period === 'string' && period.length >= 7) {
    return period.slice(0, 7)
  }
  return period
}

function formatPeriod(period) {
  const value = periodForPayload(period)
  return value ?? '—'
}

function statusLabel(status) {
  switch (status) {
    case 'draft':
      return 'Borrador'
    case 'issued':
      return 'Emitido'
    case 'canceled':
      return 'Anulado'
    default:
      return status ?? '—'
  }
}

function statusColor(status) {
  switch (status) {
    case 'draft':
      return 'grey'
    case 'issued':
      return 'success'
    case 'canceled':
      return 'error'
    default:
      return 'grey'
  }
}

function setRowLoading(id, action, value) {
  if (!rowLoading[id]) rowLoading[id] = {}
  rowLoading[id][action] = value
}

function isActionLoading(id, action) {
  return Boolean(rowLoading[id]?.[action])
}

function hasItems(item) {
  return Array.isArray(item.items) && item.items.length > 0
}

function canIssue(item) {
  if (item.status !== 'draft') return false
  if (!hasItems(item)) return false
  const totalNumber = Number(item.total ?? 0)
  return Math.abs(totalNumber) >= 0.01
}

async function fetchRows() {
  loading.value = true
  try {
    const { data } = await fetchLiquidationsRequest({
      page: options.page,
      per_page: options.itemsPerPage,
      sort_by: options.sortBy?.[0]?.key,
      sort_direction: options.sortBy?.[0]?.order,
      contract_id: props.filters.contract_id || undefined,
      status: props.filters.status || undefined,
      currency: props.filters.currency ? props.filters.currency.toUpperCase() : undefined,
      period: props.filters.period || undefined,
    })

    const records = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
        ? data
        : []

    liquidations.value = records
    total.value = data.meta?.total ?? records.length
  } catch (error) {
    console.error(error)
    snackbar.error('Error al cargar las liquidaciones')
  } finally {
    loading.value = false
  }
}

function extractErrorMessage(error, fallback) {
  if (error?.response?.data?.message) return error.response.data.message
  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0]
    if (first) return first
  }
  return fallback
}

async function handleSync(item) {
  setRowLoading(item.id, 'sync', true)
  try {
    await syncLiquidations(item.contract_id, {
      period: periodForPayload(item.period),
      currency: item.currency ? item.currency.toUpperCase() : item.currency,
    })
    snackbar.success('Liquidación sincronizada')
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo sincronizar la liquidación'))
  } finally {
    setRowLoading(item.id, 'sync', false)
  }
}

async function handleIssue(item) {
  setRowLoading(item.id, 'issue', true)
  try {
    await issueLiquidations(item.contract_id, {
      period: periodForPayload(item.period),
      currency: item.currency ? item.currency.toUpperCase() : item.currency,
    })
    snackbar.success('Liquidación emitida')
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo emitir la liquidación'))
  } finally {
    setRowLoading(item.id, 'issue', false)
  }
}

function openReopenDialog(item) {
  reopenReason.value = ''
  reopenReasonErrors.value = []
  selectedLqi.value = item
  reopenDialog.value = true
}

function closeReopenDialog() {
  reopenDialog.value = false
  reopenReason.value = ''
  reopenReasonErrors.value = []
  selectedLqi.value = null
}

async function confirmReopen() {
  const reason = reopenReason.value.trim()
  if (reason.length < 5) {
    reopenReasonErrors.value = ['Indicá un motivo (mínimo 5 caracteres).']
    return
  }

  if (!selectedLqi.value) return

  const id = selectedLqi.value.id
  setRowLoading(id, 'reopen', true)
  reopening.value = true

  try {
    await reopenLiquidations(selectedLqi.value.contract_id, {
      period: periodForPayload(selectedLqi.value.period),
      currency: selectedLqi.value.currency ? selectedLqi.value.currency.toUpperCase() : selectedLqi.value.currency,
      reason,
    })
    snackbar.success('Liquidación reabierta')
    closeReopenDialog()
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo reabrir la liquidación'))
  } finally {
    setRowLoading(id, 'reopen', false)
    reopening.value = false
  }
}

watch(
  () => [
    props.filters.contract_id,
    props.filters.period,
    props.filters.currency,
    props.filters.status,
  ],
  () => {
    options.page = 1
    fetchRows()
  },
  { immediate: true },
)

defineExpose({ reload: fetchRows })
</script>
