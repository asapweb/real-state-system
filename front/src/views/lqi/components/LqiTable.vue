<template>
  <div>
    <v-data-table-server
      v-model:items-per-page="options.itemsPerPage"
      v-model:sort-by="options.sortBy"
      v-model:page="options.page"
      :headers="headers"
      :items="rows"
      :items-length="total"
      :loading="loading"
      item-value="row_key"

      @update:options="fetchRows"
    >
      <template #item.contract="{ item }">
        <div class="d-flex flex-column">
          <span class="font-weight-medium">{{ formatModelId(item.contract_id, 'CON') }}</span>
          <small class="text-medium-emphasis" v-if="item.contract_property">
            {{ item.contract_property }}
          </small>
        </div>
      </template>

      <template #item.tenant="{ item }">
        <span>{{ item.tenant_name || '—' }}</span>
      </template>

      <template #item.currency="{ item }">
        <span>{{ item.currency }}</span>
        <v-chip
          v-if="item.contract_currency && item.contract_currency !== item.currency"
          size="x-small"
          color="warning"
          class="ml-2"
          variant="outlined"
        >
          Contrato {{ item.contract_currency }}
        </v-chip>
      </template>

      <template #item.eligibles="{ item }">
        <div class="d-flex flex-column">
          <div v-if="toNumber(item.add_total) > 0">
            <strong>ADD:</strong>
            <span>{{ item.add_count }} · {{ formatMoney(item.add_total, item.currency) }}</span>
          </div>
          <div v-if="toNumber(item.subtract_total) > 0">
            <strong>NC:</strong>
            <span>{{ item.subtract_count }} · {{ formatMoney(item.subtract_total, item.currency) }}</span>
          </div>
          <v-chip
            v-if="shouldShowLqiPending(item)"
            class="mt-1"
            size="x-small"
            color="warning"
            variant="tonal"
          >
            LQI pendiente
          </v-chip>
          <v-chip
            v-else-if="shouldShowStandaloneNcSuggested(item)"
            class="mt-1"
            size="x-small"
            color="primary"
            variant="tonal"
          >
            NC sola sugerida
          </v-chip>
          <v-chip
            v-if="shouldShowNdSuggested(item)"
            class="mt-1"
            size="x-small"
            color="info"
            variant="tonal"
          >
            ND sugerida
          </v-chip>
          <v-chip
            v-if="shouldShowNcSuggested(item)"
            class="mt-1"
            size="x-small"
            color="primary"
            variant="outlined"
          >
            NC sugerida
          </v-chip>
        </div>
      </template>

      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
          {{ statusLabel(item.status) }}
        </v-chip>
        <v-chip
          v-if="item.voucher_has_collections"
          size="x-small"
          color="error"
          class="ml-1"
          variant="outlined"
        >
          Con cobranzas
        </v-chip>
        <v-chip
          v-if="item.status === 'draft' && (!item.voucher_items_count || item.voucher_items_count === 0)"
          size="x-small"
          color="warning"
          class="ml-1"
          variant="outlined"
        >
          Sin ítems
        </v-chip>
        <v-chip
          v-if="hasAlert(item, 'pending_adjustment')"
          size="x-small"
          color="warning"
          class="ml-1"
          variant="outlined"
        >
          Ajuste pendiente
        </v-chip>
        <v-chip
          v-if="hasAlert(item, 'missing_rent')"
          size="x-small"
          color="warning"
          class="ml-1"
          variant="outlined"
        >
          Falta RENT
        </v-chip>
      </template>

      <template #item.totals="{ item }">
        <div v-if="['draft', 'issued'].includes(item.status)">
          <span>{{ formatMoney(item.voucher_total, item.currency) }}</span>
          <div v-if="item.status === 'issued' && item.voucher_issue_date" class="text-caption text-medium-emphasis">
            Emitido: {{ item.voucher_issue_date }}
          </div>
          <v-chip
            v-if="Math.abs(Number(item.voucher_total || 0)) < 0.01"
            size="x-small"
            color="warning"
            variant="outlined"
            class="mt-1"
          >
            Total = 0
          </v-chip>
        </div>
        <span v-else>—</span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex align-center justify-end flex-wrap" style="gap: 6px;">
          <v-tooltip
            v-if="showSyncButton(item)"
            :disabled="!syncTooltip(item)"
            :text="syncTooltip(item)"
            location="top"
          >
            <template #activator="{ props }">
              <span>
                <v-btn
                  v-bind="props"
                  size="small"
                  color="primary"
                  variant="text"
                  :loading="isActionLoading(item.row_key, 'sync')"
                  :disabled="actionDisabled(item, 'sync')"
                  @click="handleSync(item)"
                >
                  {{ syncLabel(item) }}
                </v-btn>
              </span>
            </template>
          </v-tooltip>

          <v-btn
            v-if="showIssueButton(item)"
            size="small"
            color="success"
            variant="flat"
            :loading="isActionLoading(item.row_key, 'issue')"
            :disabled="actionDisabled(item, 'issue')"
            @click="handleIssue(item)"
          >
            {{ issueLabel(item) }}
          </v-btn>

          <v-btn
            v-if="showPostIssueButton(item)"
            size="small"
            color="info"
            variant="tonal"
            :loading="isActionLoading(item.row_key, 'postIssue')"
            :disabled="actionDisabled(item, 'postIssue')"
            @click="handlePostIssue(item)"
          >
            Emitir ajustes post-liquidación
          </v-btn>

          <v-btn
            v-if="item.status === 'issued'"
            size="small"
            color="warning"
            variant="outlined"
            :loading="isActionLoading(item.row_key, 'reopen')"
            :disabled="actionDisabled(item, 'reopen')"
            @click="handleReopen(item)"
          >
            Reabrir
          </v-btn>

          <v-btn
            v-if="item.voucher_id"
            size="small"
            variant="text"
            color="secondary"
            :to="{ name: 'VoucherShow', params: { id: item.voucher_id } }"
          >
            Ver
          </v-btn>
          <v-btn
            v-if="item.credit_note_id"
            size="small"
            variant="text"
            color="secondary"
            :to="{ name: 'VoucherShow', params: { id: item.credit_note_id } }"
          >
            Ver NC
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
          <v-btn color="warning" :loading="reopening" @click="confirmReopenDialog">
            Reabrir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="postIssueDialog" max-width="520">
      <v-card>
        <v-card-title class="text-h6">Emitir ajustes post-liquidación</v-card-title>
        <v-card-text>
          <p class="text-body-2 mb-4">
            Confirmá la emisión de ajustes para <strong>{{ postIssueSummary }}</strong> del período
            <strong>{{ props.filters.period }}</strong>.
          </p>
          <div class="text-body-2">
            <div v-if="postIssueDetails.addCount > 0" class="mb-2">
              <strong>ND sugerida:</strong>
              {{ postIssueDetails.addCount }} · {{ formatMoney(postIssueDetails.addTotal, postIssueCurrency) }}
            </div>
            <div v-if="postIssueDetails.subtractCount > 0" class="mb-2">
              <strong>NC sugerida:</strong>
              {{ postIssueDetails.subtractCount }} · {{ formatMoney(postIssueDetails.subtractTotal, postIssueCurrency) }}
            </div>
            <div v-if="postIssueDetails.addCount === 0 && postIssueDetails.subtractCount === 0" class="text-medium-emphasis">
              Actualmente no hay cargos pendientes para este contrato.
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="postIssueProcessing" @click="closePostIssueDialog">Cancelar</v-btn>
          <v-btn color="info" :loading="postIssueProcessing" @click="confirmPostIssueDialog">
            Confirmar
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
  fetchOverview,
  syncLiquidation,
  issueLiquidation,
  reopenLiquidation,
  postIssueAdjustments,
} from '@/services/lqiService'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['overview-loaded', 'rows-count', 'features', 'post-issue-completed'])

const snackbar = useSnackbar()
const EPSILON = 0.0001

const rows = ref([])
const total = ref(0)
const loading = ref(false)
const rowLoading = reactive({})
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'contract_id', order: 'asc' }],
})

const postIssueFeatureEnabled = ref(false)
const postIssueDialog = ref(false)
const postIssueTarget = ref(null)
const postIssueProcessing = ref(false)

const headers = [
  { title: 'Contrato', key: 'contract', sortable: false },
  { title: 'Inquilino', key: 'tenant', sortable: false },
  { title: 'Moneda', key: 'currency', sortable: true },
  { title: 'Cargos período', key: 'eligibles', sortable: false },
  { title: 'Estado LQI', key: 'status', sortable: false },
  { title: 'Total LQI', key: 'totals', sortable: false, align: 'end' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const reopenDialog = ref(false)
const reopenReason = ref('')
const reopenReasonErrors = ref([])
const reopening = ref(false)
const selectedForReopen = ref(null)

const reopenSummary = computed(() => {
  if (!selectedForReopen.value) return ''
  const code = formatModelId(selectedForReopen.value.contract_id, 'CON')
  return `${code} · ${selectedForReopen.value.currency}`
})

const postIssueSummary = computed(() => {
  if (!postIssueTarget.value) return ''
  const code = formatModelId(postIssueTarget.value.contract_id, 'CON')
  return `${code} · ${postIssueTarget.value.currency}`
})

const postIssueDetails = computed(() => {
  if (!postIssueTarget.value) {
    return {
      addCount: 0,
      addTotal: 0,
      subtractCount: 0,
      subtractTotal: 0,
    }
  }

  const target = postIssueTarget.value
  return {
    addCount: Number(target.add_count ?? 0),
    addTotal: Number(target.add_pending_total ?? target.add_total ?? 0),
    subtractCount: Number(target.subtract_count ?? 0),
    subtractTotal: Number(target.subtract_pending_total ?? target.subtract_total ?? 0),
  }
})

const postIssueCurrency = computed(() => postIssueTarget.value?.currency ?? 'ARS')

watch(
  () => [
    props.filters.contract_id,
    props.filters.period,
    props.filters.currency,
    props.filters.status,
    props.filters.has_eligibles,
  ],
  () => {
    options.page = 1
    fetchRows()
  },
  { immediate: true },
)

const toNumber = (value) => Number(value ?? 0)

function hasPendingAdds(item) {
  return toNumber(item?.add_pending_total) > EPSILON
}

function hasPendingSubtracts(item) {
  return toNumber(item?.subtract_pending_total) > EPSILON
}

function shouldShowLqiPending(item) {
  return (item?.status ?? 'none') === 'none' && toNumber(item?.add_total) > EPSILON
}

function shouldShowStandaloneNcSuggested(item) {
  return (item?.status ?? 'none') === 'none'
    && toNumber(item?.add_total) <= EPSILON
    && toNumber(item?.subtract_total) > EPSILON
}

function shouldShowNdSuggested(item) {
  return (item?.status ?? 'none') === 'issued' && hasPendingAdds(item)
}

function shouldShowNcSuggested(item) {
  return (item?.status ?? 'none') === 'issued' && hasPendingSubtracts(item)
}

function syncLabel(item) {
  if ((item?.status ?? 'none') === 'none') {
    return 'Generar LQI'
  }

  return 'Sincronizar'
}

function issueLabel(item) {
  if (shouldShowStandaloneNcSuggested(item)) {
    return 'Emitir NC sola'
  }

  return 'Emitir LQI'
}

function statusLabel(status) {
  switch (status) {
    case 'draft':
      return 'Borrador'
    case 'issued':
      return 'Emitido'
    case 'none':
    default:
      return 'Sin LQI'
  }
}

function statusColor(status) {
  switch (status) {
    case 'draft':
      return 'grey'
    case 'issued':
      return 'success'
    default:
      return 'primary'
  }
}

function hasAlert(item, code) {
  const alerts = Array.isArray(item?.alerts) ? item.alerts : []
  const reasons = Array.isArray(item?.blocked_reasons) ? item.blocked_reasons : []
  return alerts.includes(code) || reasons.includes(code)
}

function isActionLoading(rowKey, action) {
  return Boolean(rowLoading[rowKey]?.[action])
}

function setActionLoading(rowKey, action, value) {
  if (!rowLoading[rowKey]) rowLoading[rowKey] = {}
  rowLoading[rowKey][action] = value
}

function actionDisabled(item, action) {
  if (action === 'sync') {
    if (item.blocked) return true
    if ((item.status ?? 'none') === 'issued') return true
    return (item.add_count ?? 0) === 0
  }
  if (action === 'issue') {
    if (item.blocked) return true

    if (shouldShowStandaloneNcSuggested(item)) {
      return toNumber(item?.subtract_total) <= EPSILON
    }

    if ((item.status ?? 'none') !== 'draft') return true
    if (toNumber(item?.add_total) <= EPSILON) return true
    return (item.voucher_items_count ?? 0) === 0
  }
  if (action === 'reopen') {
    return !item.can_reopen
  }
  if (action === 'postIssue') {
    if (!postIssueFeatureEnabled.value) return true
    if ((item.status ?? 'none') !== 'issued') return true
    if (item.blocked) return true
    return !(hasPendingAdds(item) || hasPendingSubtracts(item))
  }
  return false
}

function showSyncButton(item) {
  return ['none', 'draft'].includes(item.status ?? 'none')
}

function showIssueButton(item) {
  if (shouldShowStandaloneNcSuggested(item)) {
    return true
  }

  return (item.status ?? 'none') === 'draft'
}

function showPostIssueButton(item) {
  if (!postIssueFeatureEnabled.value) return false
  if (item.blocked) return false
  return (item.status ?? 'none') === 'issued' && (hasPendingAdds(item) || hasPendingSubtracts(item))
}

async function fetchRows() {
  loading.value = true
  try {
    const { data } = await fetchOverview({
      ...props.filters,
      page: options.page,
      per_page: options.itemsPerPage,
    })

    rows.value = (data.data ?? []).map((row) => {
      const addTotal = Number(row?.add_total ?? 0)
      const subtractTotal = Number(row?.subtract_total ?? 0)
      const addPendingTotal = Number(row?.add_pending_total ?? row?.add_total ?? 0)
      const subtractPendingTotal = Number(row?.subtract_pending_total ?? row?.subtract_total ?? 0)

      return {
        ...row,
        status: typeof row?.status === 'string' ? row.status : 'none',
        add_total: addTotal,
        subtract_total: subtractTotal,
        add_pending_total: addPendingTotal,
        subtract_pending_total: subtractPendingTotal,
        add_count: Number(row?.add_count ?? 0),
        subtract_count: Number(row?.subtract_count ?? 0),
        only_credit: Boolean(row?.only_credit),
        nc_suggested: Boolean(row?.nc_suggested),
        blocked: Boolean(row?.blocked),
      }
    })
    total.value = data.meta?.total ?? rows.value.length

    const features = data.features ?? {}
    postIssueFeatureEnabled.value = Boolean(features?.lqi?.post_issue)
    emit('features', features)

    const availableCurrencies = data.available_currencies ?? []
    emit('overview-loaded', { availableCurrencies })
  } catch (error) {
    console.error(error)
    snackbar.error('Error al cargar las liquidaciones')
    postIssueFeatureEnabled.value = false
    emit('features', {})
  } finally {
    loading.value = false
    emit('rows-count', { total: total.value })
  }
}

async function handleSync(item) {
  if (item.blocked_reasons?.includes('pending_adjustment')) {
    snackbar.info('Ajuste pendiente del período — no se puede generar.')
    return
  }

  if (item.blocked_reasons?.includes('missing_rent')) {
    snackbar.info('Falta la cuota RENT del período — no se puede generar.')
    return
  }

  if ((item.add_count ?? 0) === 0) {
    if ((item.subtract_count ?? 0) > 0) {
      snackbar.info('Sin elegibles positivos — Solo créditos (NC sugerida).')
    } else {
      snackbar.info('Omitido: sin cargos elegibles.')
    }
    return
  }

  setActionLoading(item.row_key, 'sync', true)
  try {
    await syncLiquidation(item.contract_id, {
      period: props.filters.period,
      currency: item.currency,
    })
    snackbar.success('Borrador actualizado.')
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo sincronizar la liquidación'))
  } finally {
    setActionLoading(item.row_key, 'sync', false)
  }
}

function syncTooltip(item) {
  if (!actionDisabled(item, 'sync')) {
    return ''
  }

  if (item.blocked_reasons?.includes('pending_adjustment')) {
    return 'Ajuste pendiente del período'
  }

  if (item.blocked_reasons?.includes('missing_rent')) {
    return 'Falta la cuota de renta del período'
  }

  if ((item.add_count ?? 0) === 0) {
    if ((item.subtract_count ?? 0) > 0) {
      return 'Solo créditos (emitir NC)'
    }
    return 'Sin cargos elegibles'
  }

  if ((item.status ?? '') === 'issued') {
    return 'La liquidación ya está emitida'
  }

  return ''
}

async function handleIssue(item) {
  if (item.blocked_reasons?.includes('pending_adjustment')) {
    snackbar.info('Ajuste pendiente del período — no se puede generar.')
    return
  }

  if (item.blocked_reasons?.includes('missing_rent')) {
    snackbar.info('Falta la cuota RENT del período — no se puede generar.')
    return
  }

  const hasAdd = (item.add_count ?? 0) > 0
  const hasSubtract = (item.subtract_count ?? 0) > 0

  if (!hasAdd && !hasSubtract) {
    snackbar.info('Sin cargos del período.')
    return
  }

  if (hasAdd) {
    if ((item.status ?? '') !== 'draft' || (item.add_total ?? 0) <= 0 || (item.voucher_items_count ?? 0) === 0) {
      snackbar.info('Omitido: el borrador no tiene ítems o total válido.')
      return
    }
  }

  if (!hasAdd && hasSubtract) {
    // allow NC only flow
  } else if (!hasAdd) {
    snackbar.info('Sin cargos del período.')
    return
  }

  setActionLoading(item.row_key, 'issue', true)
  try {
    await issueLiquidation(item.contract_id, {
      period: props.filters.period,
      currency: item.currency,
    })
    if (!hasAdd && hasSubtract) {
      snackbar.success('Emitida NC del período (sin LQI).')
    } else {
      const messages = ['LQI emitida']
      if (hasSubtract) {
        messages.push('NC asociada emitida')
      }
      snackbar.success(messages.join(' · '))
    }
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo emitir la liquidación'))
  } finally {
    setActionLoading(item.row_key, 'issue', false)
  }
}

function handleReopen(item) {
  selectedForReopen.value = item
  reopenReason.value = ''
  reopenReasonErrors.value = []
  reopenDialog.value = true
}

function closeReopenDialog() {
  reopenDialog.value = false
  reopenReasonErrors.value = []
  selectedForReopen.value = null
}

async function confirmReopenDialog() {
  const reason = reopenReason.value.trim()
  if (reason.length < 5) {
    reopenReasonErrors.value = ['Indicá un motivo (mínimo 5 caracteres).']
    return
  }

  if (!selectedForReopen.value) return

  const item = selectedForReopen.value
  setActionLoading(item.row_key, 'reopen', true)
  reopening.value = true
  try {
    await reopenLiquidation(item.contract_id, {
      period: props.filters.period,
      currency: item.currency,
      reason,
    })
    snackbar.success('Liquidación reabierta')
    closeReopenDialog()
    fetchRows()
  } catch (error) {
    snackbar.error(extractErrorMessage(error, 'No se pudo reabrir la liquidación'))
  } finally {
    setActionLoading(item.row_key, 'reopen', false)
    reopening.value = false
  }
}

function handlePostIssue(item) {
  postIssueTarget.value = item
  postIssueProcessing.value = false
  postIssueDialog.value = true
}

function closePostIssueDialog() {
  postIssueDialog.value = false
  postIssueProcessing.value = false
  postIssueTarget.value = null
}

async function confirmPostIssueDialog() {
  if (!postIssueTarget.value) return

  const item = postIssueTarget.value
  postIssueProcessing.value = true
  setActionLoading(item.row_key, 'postIssue', true)

  try {
    const { data } = await postIssueAdjustments(item.contract_id, props.filters.period, item.currency)
    handlePostIssueResponse(data, item)
  } catch (error) {
    const status = error?.response?.status
    if (status === 403) {
      postIssueFeatureEnabled.value = false
    }
    snackbar.error(extractErrorMessage(error, 'No se pudieron emitir los ajustes'))
    closePostIssueDialog()
  } finally {
    setActionLoading(item.row_key, 'postIssue', false)
    postIssueProcessing.value = false
  }
}

function handlePostIssueResponse(data, item) {
  const result = data?.result

  if (result === 'ok') {
    const messages = []
    if (data?.nd?.issued) {
      messages.push(`ND emitida: ${formatMoney(data.nd.total, item.currency)}`)
    }
    if (data?.nc?.issued) {
      messages.push(`NC emitida: ${formatMoney(data.nc.total, item.currency)}`)
    }

    snackbar.success(messages.length ? messages.join(' · ') : 'Sin ajustes emitidos.')
    closePostIssueDialog()
    fetchRows()
    emit('post-issue-completed')
    return
  }

  if (result === 'blocked') {
    const reasons = Array.isArray(data?.reasons) ? data.reasons : []
    const reasonLabel = reasons.map(translateReason).filter(Boolean).join(', ') || 'Bloqueo desconocido'
    snackbar.info(`Bloqueado: ${reasonLabel}`)
    closePostIssueDialog()
    return
  }

  if (result === 'missing_lqi_for_adds') {
    snackbar.info('Emití primero la LQI del período para poder generar la ND.')
    closePostIssueDialog()
    return
  }

  if (result === 'nothing_to_issue') {
    snackbar.info('Sin ajustes pendientes.')
    closePostIssueDialog()
    return
  }

  snackbar.error('No se pudieron emitir los ajustes post-liquidación.')
  closePostIssueDialog()
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

function translateReason(code) {
  switch (code) {
    case 'pending_adjustment':
      return 'Ajuste pendiente'
    case 'missing_rent':
      return 'Falta cuota RENT'
    default:
      return typeof code === 'string' ? code : ''
  }
}

defineExpose({
  reload: fetchRows,
  rows,
})
</script>
