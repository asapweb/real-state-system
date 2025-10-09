<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="text-h5">Gestión de Liquidaciones Inquilinos</h2>
    </div>
    <!-- <div class="text-h6 me-2">{{ formatPeriodWithMonthName(period).charAt(0).toUpperCase() + formatPeriodWithMonthName(period).slice(1) }}</div> -->
  </div>
  <div>


    <v-row dense class="mb-12" v-if="kpis.overall">
      <v-col cols="12" sm="6" md="2">
        <KpiCard
          title="Contratos activos"
          tooltip="Contratos con vigencia en el período."
          :value="kpis.overall.active_contracts || 0"
          :subtitle="`${kpis.overall.contracts_with_eligibles} elegibles`"
          :loading="loadingKpis"
        />
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <KpiCard
          title="Bloqueados"
          tooltip="Contratos omitidos por falta de RENT o ajustes pendientes."
          :subtitle="`${kpis.overall.blocked.pending_adjustment || 0} ajustes y ${kpis.overall.blocked.missing_rent || 0} cuotas pendientes`"
          :value="`${kpis.overall.blocked.total}`"
          :loading="loadingKpis"
        />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <KpiCard
          title="NC a emitir"
          tooltip="Contratos con únicamente cargos negativos pendientes de emitir."
          :value="kpis.overall.credit?.total || 0"
          :subtitle="`${kpis.overall.credit?.only || 0} solo NC`"
          :loading="loadingKpis"
        />
      </v-col>
      <v-col cols="12" sm="6" md="2">
        <KpiCardProgressLine
          title="Cobertura de cargos"
          tooltip="Cargos del período tocados por LQI/NC/ND (borrador o emitido)."
          :value="kpis.overall.charges_coverage"
          :subtitle="kpis.overall.charges_coverage_detail.universe > 0
            ? `${kpis.overall.charges_coverage_detail.touched} de ${kpis.overall.charges_coverage_detail.universe}`
            : 'Sin cargos'"
          :loading="loadingKpis"
          :hover="false"
        />
      </v-col>
      <v-col cols="12" sm="6" md="2">
        <KpiCardProgressLine
          title="Cobertura de emisión"
          tooltip="Liquidaciones emitidas sobre el universo elegible."
          :value="kpis.overall.issuance_coverage"
          :subtitle="kpis.overall.issuance_coverage_detail.universe > 0
            ? `${kpis.overall.issuance_coverage_detail.issued} de ${kpis.overall.issuance_coverage_detail.universe}`
            : 'Sin LQI esperadas'"
          :loading="loadingKpis"
          :hover="false"
        />
      </v-col>
    </v-row>
    <v-dialog v-model="showStatsDialog" max-width="900">
      <v-card>
        <v-card-title class="text-h6 d-flex align-center">
          <v-icon class="mr-2">mdi-chart-bar</v-icon>
          Resumen de KPIs
          <v-spacer />
          <v-btn icon @click="showStatsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="4">
              <v-card :loading="loadingKpis" outlined>
                <v-card-text>
                  <div class="text-subtitle-1 mb-2">Resumen general</div>
                  <div class="d-flex flex-column gap-1">
                    <div><strong>Contratos activos:</strong> {{ kpis.overall.active_contracts }}</div>
                    <div><strong>Contratos con elegibles (add):</strong> {{ kpis.overall.contracts_with_eligibles }}</div>
                    <div><strong>Procesables sin bloqueos:</strong> {{ kpis.overall.contracts_with_eligibles_processable }}</div>
                    <div><strong>NC a emitir:</strong> {{ kpis.overall.credit?.total ?? 0 }} <span class="text-body-2">(Solo NC: {{ kpis.overall.credit?.only ?? 0 }})</span></div>
                    <div>
                      <strong>NC emitidas:</strong>
                      {{ kpis.overall.nc.issued_count }} · {{ formatMoney(kpis.overall.nc.issued_total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}
                      <div class="text-body-2 ml-2">Asociadas: {{ kpis.overall.nc.associated_count }} · {{ formatMoney(kpis.overall.nc.associated_total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}</div>
                      <div class="text-body-2 ml-2">Solas: {{ kpis.overall.nc.standalone_count }} · {{ formatMoney(kpis.overall.nc.standalone_total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}</div>
                    </div>
                    <div><strong>Omitidos por ajuste pendiente:</strong> {{ kpis.overall.blocked.pending_adjustment }}</div>
                    <div><strong>Omitidos por falta de RENT:</strong> {{ kpis.overall.blocked.missing_rent }}</div>
                    <div><strong>Total elegible neto:</strong> {{ formatMoney(kpis.overall.eligible_total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}</div>
                    <div><strong>Borradores:</strong> {{ kpis.overall.draft.count }} · {{ formatMoney(kpis.overall.draft.total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}</div>
                    <div><strong>Emitidos:</strong> {{ kpis.overall.issued.count }} · {{ formatMoney(kpis.overall.issued.total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}</div>
                    <div><strong>Cobertura de cargos:</strong> {{ formatCoverage(kpis.overall.charges_coverage) }}</div>
                    <div><strong>Cobertura de emisión:</strong> {{ formatCoverage(kpis.overall.issuance_coverage) }}</div>
                  </div>
                  <div class="mt-3">
                    <span class="text-caption text-medium-emphasis">Alertas</span>
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                      <v-chip size="x-small" color="warning" variant="tonal">
                        Sin inquilino: {{ kpis.alerts.missing_tenant }}
                      </v-chip>
                      <v-chip size="x-small" color="warning" variant="tonal">
                        Moneda distinta: {{ kpis.alerts.currency_mismatch }}
                      </v-chip>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <template v-if="filters.currency === 'ALL'">
              <v-col
                v-for="currencyMetric in kpis.currencies"
                :key="currencyMetric.currency"
                cols="12"
                md="4"
              >
                <v-card outlined :loading="loadingKpis">
                  <v-card-text>
                    <div class="text-subtitle-1 mb-2">Moneda {{ currencyMetric.currency }}</div>
                    <div class="d-flex flex-column gap-1">
                      <div><strong>Contratos con elegibles (add):</strong> {{ currencyMetric.contracts_with_eligibles }}</div>
                      <div><strong>Procesables sin bloqueos:</strong> {{ currencyMetric.contracts_with_eligibles_processable }}</div>
                      <div><strong>NC a emitir:</strong> {{ currencyMetric.credit?.total ?? 0 }} <span class="text-body-2">(Solo NC: {{ currencyMetric.credit?.only ?? 0 }})</span></div>
                      <div>
                        <strong>NC emitidas:</strong>
                        {{ currencyMetric.nc?.issued_count ?? 0 }} · {{ formatMoney(currencyMetric.nc?.issued_total ?? 0, currencyMetric.currency) }}
                        <div class="text-body-2 ml-2">Asociadas: {{ currencyMetric.nc?.associated_count ?? 0 }} · {{ formatMoney(currencyMetric.nc?.associated_total ?? 0, currencyMetric.currency) }}</div>
                        <div class="text-body-2 ml-2">Solas: {{ currencyMetric.nc?.standalone_count ?? 0 }} · {{ formatMoney(currencyMetric.nc?.standalone_total ?? 0, currencyMetric.currency) }}</div>
                      </div>
                      <div><strong>Omitidos por ajuste pendiente:</strong> {{ currencyMetric.blocked?.pending_adjustment ?? 0 }}</div>
                      <div><strong>Omitidos por falta de RENT:</strong> {{ currencyMetric.blocked?.missing_rent ?? 0 }}</div>
                      <div><strong>Total elegible neto:</strong> {{ formatMoney(currencyMetric.eligible_total, currencyMetric.currency) }}</div>
                      <div><strong>Borradores:</strong> {{ currencyMetric.draft?.count ?? 0 }} · {{ formatMoney(currencyMetric.draft?.total ?? 0, currencyMetric.currency) }}</div>
                      <div><strong>Emitidos:</strong> {{ currencyMetric.issued?.count ?? 0 }} · {{ formatMoney(currencyMetric.issued?.total ?? 0, currencyMetric.currency) }}</div>
                      <div><strong>Cobertura de cargos:</strong> {{ formatCoverage(currencyMetric.charges_coverage) }}</div>
                      <div><strong>Cobertura de emisión:</strong> {{ formatCoverage(currencyMetric.issuance_coverage) }}</div>
                      <div class="d-flex align-center justify-space-between">
                        <div>Cobertura de emisión</div>
                        <div>
                          <template v-if="currencyMetric.issuance_coverage_detail.universe > 0">
                            {{ formatCoverage(currencyMetric.issuance_coverage) }} ({{ currencyMetric.issuance_coverage_detail.issued }} de {{ currencyMetric.issuance_coverage_detail.universe }})
                          </template>
                          <template v-else>
                            Sin LQI esperadas
                          </template>
                        </div>
                      </div>
                      <v-progress-linear
                        v-if="currencyMetric.issuance_coverage !== null"
                        :model-value="currencyMetric.issuance_coverage"
                        color="primary"
                        height="6"
                        class="my-1 rounded-pill"
                      />
                      <div class="d-flex align-center justify-space-between">
                        <div>Cobertura de cargos</div>
                        <div>
                          <template v-if="currencyMetric.charges_coverage_detail.universe > 0">
                            {{ formatCoverage(currencyMetric.charges_coverage) }} ({{ currencyMetric.charges_coverage_detail.touched }} de {{ currencyMetric.charges_coverage_detail.universe }})
                          </template>
                          <template v-else>
                            Sin cargos
                          </template>
                        </div>
                      </div>
                      <v-progress-linear
                        v-if="currencyMetric.charges_coverage !== null"
                        :model-value="currencyMetric.charges_coverage"
                        color="success"
                        height="6"
                        class="my-1 rounded-pill"
                      />
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </template>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>
    <v-sheet class="my-4" border rounded>
    <v-toolbar flat color="transparent" class="px-2">

      <v-btn
        @click="filterDrawer = !filterDrawer"
        prepend-icon="mdi-filter-variant"
        variant="outlined"
        color="grey-darken-1"
      >
        Filtros
        <v-badge
          v-if="activeFilterCount > 0"
          :content="activeFilterCount"
          color="primary"
          floating
          class="ml-2"
        ></v-badge>
      </v-btn>

      <v-spacer></v-spacer>


      <v-btn
        icon
        color="primary"
        @click="showStatsDialog = true"
        :title="'Ver resumen de KPIs'"
        class="mr-2"
      >
        <v-icon>mdi-chart-bar</v-icon>
      </v-btn>
      <v-btn
        color="primary"
        variant="text"
        size="small"
        :loading="generating"
        :disabled="!canGenerate || rowsEmpty || generating || issuing || reopening || postIssuing"
        @click="requestBulk('generate')"
      >Generar Liquidaciones</v-btn>
      <v-btn
        color="success"
        variant="text"
        size="small"
        :loading="generating"
        :disabled="!canGenerate || rowsEmpty || generating || issuing || reopening || postIssuing"
        @click="requestBulk('issue')"
      >Emitir borradores</v-btn>
      <v-btn
        color="info"
        variant="text"
        size="small"
        :loading="generating"
        :disabled="!canGenerate || rowsEmpty || generating || issuing || reopening || postIssuing"
        @click="requestBulk('post_issue')"
      >Emitir ajustes post-liquidación</v-btn>
      <v-btn
        color="warning"
        variant="text"
        size="small"
        :loading="generating"
        :disabled="!canGenerate || rowsEmpty || generating || issuing || reopening || postIssuing"
        @click="requestBulk('reopen')"
      >Reabrir emitidas</v-btn>

    </v-toolbar>
  </v-sheet>
    <v-card class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" sm="4" md="3">
            <v-text-field
              v-model="filters.period"
              type="month"
              label="Período (YYYY-MM)"
              variant="solo-filled"
              hide-details
              required
            />
          </v-col>
          <v-col cols="12" sm="4" md="3">
            <v-select
              v-model="filters.currency"
              :items="currencyOptions"
              item-title="title"
              item-value="value"
              label="Moneda"
              variant="solo-filled"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4" md="3">
            <ContractAutocomplete
              v-model="filters.contract_id"
              label="Contrato"
              variant="solo-filled"
              flat
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4" md="3">
            <v-select
              v-model="filters.status"
              :items="statusOptions"
              item-title="title"
              item-value="value"
              label="Estado"
              variant="solo-filled"
              hide-details
              clearable
            />
          </v-col>
          <v-col cols="12" class="pt-4">
            <div class="d-flex align-center" style="gap: 12px;">
              <span class="text-medium-emphasis text-caption">Filtros rápidos:</span>
              <v-chip
                :color="filters.has_eligibles === true ? 'primary' : undefined"
                :variant="filters.has_eligibles === true ? 'elevated' : 'tonal'"
                size="small"
                @click="toggleEligibles()"
              >
                Con elegibles
              </v-chip>
              <v-chip
                :color="filters.status === 'draft' ? 'primary' : undefined"
                :variant="filters.status === 'draft' ? 'elevated' : 'tonal'"
                size="small"
                @click="setStatusQuick('draft')"
              >
                Borrador
              </v-chip>
              <v-chip
                :color="filters.status === 'issued' ? 'primary' : undefined"
                :variant="filters.status === 'issued' ? 'elevated' : 'tonal'"
                size="small"
                @click="setStatusQuick('issued')"
              >
                Emitido
              </v-chip>
              <v-chip
                v-if="filters.has_eligibles === true || ['draft','issued'].includes(filters.status)"
                class="ml-2"
                color="grey"
                size="small"
                variant="tonal"
                prepend-icon="mdi-close"
                @click="resetQuickFilters"
              >
                Limpiar
              </v-chip>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <LqiTable
      ref="tableRef"
      :filters="filters"
      @overview-loaded="onOverviewLoaded"
      @rows-count="onRowsCount"
      @features="onFeatures"
      @post-issue-completed="onPostIssueCompleted"
    />

    <v-dialog v-model="bulkDialog.open" max-width="520">
      <v-card>
        <v-card-title class="text-h6">Confirmar {{ bulkActionTitle }}</v-card-title>
        <v-card-text>
          <p class="text-body-2 mb-3">Se procesarán todas las LQI del listado actual.</p>
          <div class="text-body-2">
            <div><strong>Período:</strong> {{ filters.period }}</div>
            <div><strong>Moneda:</strong> {{ currencyLabel }}</div>
            <div><strong>Contrato:</strong> {{ contractLabel }}</div>
            <div><strong>Estado:</strong> {{ statusFilterLabel }}</div>
          </div>
          <div
            v-if="bulkDialog.action === 'post_issue'"
            class="text-body-2 mt-4"
          >
            <div><strong>Filas a procesar:</strong> {{ bulkDialog.summary.rows }}</div>
            <div>
              <strong>ND estimadas:</strong>
              {{ bulkDialog.summary.add.count }} ·
              {{ formatMoney(bulkDialog.summary.add.total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}
            </div>
            <div>
              <strong>NC estimadas:</strong>
              {{ bulkDialog.summary.subtract.count }} ·
              {{ formatMoney(bulkDialog.summary.subtract.total, filters.currency === 'ALL' ? 'ARS' : filters.currency) }}
            </div>
            <div
              class="text-caption mt-2"
              v-if="bulkDialog.summary.excluded.none || bulkDialog.summary.excluded.draft"
            >
              Omitidas por estado — Sin LQI: {{ bulkDialog.summary.excluded.none }} · Borrador: {{ bulkDialog.summary.excluded.draft }}
            </div>
            <div
              class="text-caption"
              v-if="bulkDialog.summary.issuedWithoutPending"
            >
              Emitidas sin pendientes: {{ bulkDialog.summary.issuedWithoutPending }}
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="cancelBulkDialog">Cancelar</v-btn>
          <v-btn color="primary" @click="confirmBulkAction">Confirmar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import LqiTable from './components/LqiTable.vue'
import KpiCard from '@/views/components/KpiCard.vue'
import KpiCardProgressLine from '@/views/components/KpiCardProgressLine.vue'
import {
  fetchKpis,
  generateBatch,
  issueBatch,
  reopenBatch,
  postIssueAdjustmentsBulk,
} from '@/services/lqiService'
import { formatMoney } from '@/utils/money'

const showStatsDialog = ref(false)
const snackbar = useSnackbar()
const tableRef = ref(null)
const generating = ref(false)
const issuing = ref(false)
const reopening = ref(false)
const postIssuing = ref(false)
const loadingKpis = ref(false)
const dynamicCurrencies = ref(new Set(['ARS', 'USD']))
const rowsMeta = reactive({ total: 0 })
const bulkDialog = reactive({ open: false, action: null, summary: defaultPostIssueSummary() })
const postIssueFeatureEnabled = ref(false)
const SUMMARY_EPSILON = 0.0001

const filters = reactive({
  period: currentPeriod(),
  currency: 'ALL',
  contract_id: null,
  status: null,
  has_eligibles: null,
})

const kpis = reactive(defaultKpis())

function defaultKpis() {
  return {
    currencies: [],
    overall: {
      active_contracts: 0,
      contracts_with_eligibles: 0,
      contracts_with_eligibles_processable: 0,
      eligible_total: 0,
      blocked: {
        pending_adjustment: 0,
        missing_rent: 0,
        total: 0,
      },
      credit: {
        total: 0,
        only: 0,
      },
      nc: {
        issued_count: 0,
        issued_total: 0,
        associated_count: 0,
        associated_total: 0,
        standalone_count: 0,
        standalone_total: 0,
      },
      draft: {
        count: 0,
        total: 0,
      },
      issued: {
        count: 0,
        total: 0,
      },
      issuance_coverage: null,
      issuance_coverage_detail: {
        universe: 0,
        universe_total: 0,
        issued: 0,
      },
      charges_coverage: null,
      charges_coverage_issued: null,
      charges_coverage_detail: {
        universe: 0,
        touched: 0,
        issued: 0,
      },
    },
    alerts: {
      missing_tenant: 0,
      currency_mismatch: 0,
    },
  }
}

function defaultPostIssueSummary() {
  return {
    rows: 0,
    add: { count: 0, total: 0 },
    subtract: { count: 0, total: 0 },
    excluded: {
      none: 0,
      draft: 0,
      other: 0,
    },
    issuedWithoutPending: 0,
  }
}

const statusOptions = [
  { title: 'Todos', value: null },
  { title: 'Sin LQI', value: 'none' },
  { title: 'Borrador', value: 'draft' },
  { title: 'Emitido', value: 'issued' },
]

const statusLabelsMap = {
  none: 'Sin LQI',
  draft: 'Borrador',
  issued: 'Emitido',
}

const bulkActionLabels = {
  generate: 'Generar liquidaciones del período',
  issue: 'Emitir borradores',
  reopen: 'Reabrir emitidas',
  post_issue: 'Emitir ajustes post-liquidación (bulk)',
}

const bulkActionTitle = computed(() => bulkActionLabels[bulkDialog.action] ?? 'Acción masiva')
const currencyLabel = computed(() => (filters.currency === 'ALL' || !filters.currency ? 'Todas' : filters.currency))
const contractLabel = computed(() => (filters.contract_id ? `Contrato #${filters.contract_id}` : 'Todos'))
const statusFilterLabel = computed(() => {
  if (!filters.status) return 'Todos'
  return statusLabelsMap[filters.status] ?? filters.status
})

const currencyOptions = computed(() => {
  const items = [{ title: 'Todas', value: 'ALL' }]
  const uniques = Array.from(dynamicCurrencies.value).sort()
  uniques.forEach((currency) => {
    items.push({ title: currency, value: currency })
  })
  return items
})

const canGenerate = computed(() => Boolean(filters.period))
const rowsEmpty = computed(() => rowsMeta.total === 0)

watch(
  () => [filters.period, filters.currency, filters.contract_id, filters.status, filters.has_eligibles],
  () => {
    rowsMeta.total = 0
    loadKpis()
  },
  { immediate: true },
)

function onOverviewLoaded(payload) {
  const currencies = payload?.availableCurrencies ?? []
  if (currencies.length) {
    const next = new Set(dynamicCurrencies.value)
    currencies.forEach((c) => next.add(c))
    dynamicCurrencies.value = next
  }
}

function onRowsCount(payload) {
  rowsMeta.total = payload?.total ?? 0
}

function onFeatures(payload) {
  const enabled = Boolean(payload?.lqi?.post_issue)
  postIssueFeatureEnabled.value = enabled
}

function onPostIssueCompleted() {
  loadKpis()
}

async function loadKpis() {
  loadingKpis.value = true
  try {
    const { data } = await fetchKpis({ ...filters })

    const defaults = defaultKpis()
    const overall = data?.overall ?? {}

    kpis.overall = {
      ...defaults.overall,
      ...overall,
      blocked: {
        ...defaults.overall.blocked,
        ...(overall.blocked ?? {}),
      },
      draft: {
        ...defaults.overall.draft,
        ...(overall.draft ?? {}),
      },
      issued: {
        ...defaults.overall.issued,
        ...(overall.issued ?? {}),
      },
      issuance_coverage_detail: {
        ...defaults.overall.issuance_coverage_detail,
        ...(overall.issuance_coverage_detail ?? {}),
      },
      charges_coverage_issued: overall.charges_coverage_issued ?? defaults.overall.charges_coverage_issued,
      charges_coverage_detail: {
        ...defaults.overall.charges_coverage_detail,
        ...(overall.charges_coverage_detail ?? {}),
      },
    }

    kpis.alerts = {
      ...defaults.alerts,
      ...(data?.alerts ?? {}),
    }

    const currencyData = Array.isArray(data?.currencies) ? data.currencies : []
    if (currencyData.length) {
      const next = new Set(dynamicCurrencies.value)
      currencyData.forEach((metric) => {
        if (metric.currency) next.add(metric.currency)
      })
      dynamicCurrencies.value = next
    }

    kpis.currencies = currencyData.map((metric) => {
      const credit = metric?.credit ?? {}
      const nc = metric?.nc ?? {}
      const draft = metric?.draft ?? {}
      const issued = metric?.issued ?? {}
      const issuanceCoverageDetail = metric?.issuance_coverage_detail ?? {}
      const chargesCoverageDetail = metric?.charges_coverage_detail ?? {}

      return {
        ...metric,
        contracts_with_eligibles_processable: metric?.contracts_with_eligibles_processable ?? 0,
        credit: {
          total: credit.total ?? 0,
          only: credit.only ?? 0,
        },
        nc: {
          issued_count: nc.issued_count ?? 0,
          issued_total: nc.issued_total ?? 0,
          associated_count: nc.associated_count ?? 0,
          associated_total: nc.associated_total ?? 0,
          standalone_count: nc.standalone_count ?? 0,
          standalone_total: nc.standalone_total ?? 0,
        },
        draft: {
          count: draft.count ?? 0,
          total: draft.total ?? 0,
        },
        issued: {
          count: issued.count ?? 0,
          total: issued.total ?? 0,
        },
        issuance_coverage: metric.issuance_coverage ?? null,
        issuance_coverage_detail: {
          universe: issuanceCoverageDetail.universe ?? 0,
          universe_total: issuanceCoverageDetail.universe_total ?? 0,
          issued: issuanceCoverageDetail.issued ?? 0,
        },
        charges_coverage: metric.charges_coverage ?? null,
        charges_coverage_issued: metric.charges_coverage_issued ?? null,
        charges_coverage_detail: {
          universe: chargesCoverageDetail.universe ?? 0,
          touched: chargesCoverageDetail.touched ?? 0,
          issued: chargesCoverageDetail.issued ?? 0,
        },
      }
    })
  } catch (error) {
    console.error(error)
    snackbar.error('No se pudieron cargar los indicadores')
  } finally {
    loadingKpis.value = false
  }
}
function requestBulk(action) {
  if (!canGenerate.value || rowsEmpty.value) return
  bulkDialog.action = action
  bulkDialog.summary = action === 'post_issue' ? buildPostIssueSummary() : defaultPostIssueSummary()
  bulkDialog.open = true
}

async function confirmBulkAction() {
  const action = bulkDialog.action
  bulkDialog.open = false
  if (!action) return

  if (action === 'generate') {
    await executeGenerate()
  } else if (action === 'issue') {
    await executeIssue()
  } else if (action === 'reopen') {
    await executeReopen()
  } else if (action === 'post_issue') {
    await executePostIssue()
  }

  bulkDialog.action = null
  bulkDialog.summary = defaultPostIssueSummary()
}

function cancelBulkDialog() {
  bulkDialog.open = false
  bulkDialog.action = null
  bulkDialog.summary = defaultPostIssueSummary()
}

async function executeGenerate() {
  generating.value = true
  try {
    const payload = buildActionPayload()
    const { data } = await generateBatch(payload)
    const updated = data.updated ?? 0
    const summary = summarizeSkipped(data.skipped)
    snackbar.success(
      formatGenerateSummary(
        updated,
        summary,
        data.credit_suggested ?? 0,
        data.credit_only ?? 0,
      ),
    )
    postActionRefresh()
  } catch (error) {
    handleActionError(error, 'No se pudo generar')
  } finally {
    generating.value = false
  }
}

async function executeIssue() {
  issuing.value = true
  try {
    const payload = buildActionPayload()
    const { data } = await issueBatch(payload)
    const issued = data.issued ?? 0
    const summary = summarizeSkipped(data.skipped)
    snackbar.success(formatIssueSummary(issued, summary, data.credit_issued ?? 0, data.credit_only_issued ?? 0))
    postActionRefresh()
  } catch (error) {
    handleActionError(error, 'No se pudieron emitir las liquidaciones')
  } finally {
    issuing.value = false
  }
}

async function executeReopen() {
  reopening.value = true
  try {
    const payload = buildActionPayload()
    const { data } = await reopenBatch(payload)
    const reopened = data.reopened ?? 0
    const summary = summarizeSkipped(data.skipped)
    snackbar.success(formatReopenSummary(reopened, summary))
    postActionRefresh()
  } catch (error) {
    handleActionError(error, 'No se pudieron reabrir las liquidaciones')
  } finally {
    reopening.value = false
  }
}

function buildPostIssueSummary() {
  const rowsRef = tableRef.value?.rows
  const tableRows = Array.isArray(rowsRef?.value) ? rowsRef.value : []

  const summary = defaultPostIssueSummary()

  tableRows.forEach((row) => {
    const status = row?.status ?? 'none'
    const pendingAdd = Number(row?.add_pending_total ?? row?.add_total ?? 0)
    const pendingSubtract = Number(row?.subtract_pending_total ?? row?.subtract_total ?? 0)

    if (status === 'issued') {
      if (pendingAdd > SUMMARY_EPSILON || pendingSubtract > SUMMARY_EPSILON) {
        summary.rows += 1
        summary.add.count += Number(row?.add_count ?? 0)
        summary.add.total += pendingAdd
        summary.subtract.count += Number(row?.subtract_count ?? 0)
        summary.subtract.total += pendingSubtract
      } else {
        summary.issuedWithoutPending += 1
      }
      return
    }

    if (status === 'none') {
      summary.excluded.none += 1
      return
    }

    if (status === 'draft') {
      summary.excluded.draft += 1
      return
    }

    summary.excluded.other += 1
  })

  summary.add.total = Number(summary.add.total.toFixed(2))
  summary.subtract.total = Number(summary.subtract.total.toFixed(2))

  return summary
}

async function executePostIssue() {
  postIssuing.value = true
  try {
    const params = buildPostIssueQuery()
    const { data } = await postIssueAdjustmentsBulk(filters.period, params)
    snackbar.success(formatPostIssueSummary(data))
    postActionRefresh()
  } catch (error) {
    handleActionError(error, 'No se pudieron emitir los ajustes post-liquidación')
  } finally {
    postIssuing.value = false
  }
}

function buildActionPayload() {
  return {
    period: filters.period,
    currency: filters.currency,
    contract_id: filters.contract_id,
    status: filters.status,
    has_eligibles: filters.has_eligibles,
    allow_empty: false,
  }
}

function buildPostIssueQuery() {
  return {
    currency: filters.currency,
    contract_id: filters.contract_id,
    state: filters.status,
    has_eligibles: filters.has_eligibles,
  }
}

function summarizeSkipped(skipped = []) {
  const summary = { total: 0, counts: {} }
  if (!Array.isArray(skipped)) {
    return summary
  }

  summary.total = skipped.length

  skipped.forEach((item) => {
    const baseReasons = Array.isArray(item?.reasons) && item.reasons.length
      ? item.reasons
      : (item?.reason ? [item.reason] : [])

    const reasons = Array.from(new Set(baseReasons.filter(Boolean)))
    reasons.forEach((reason) => {
      summary.counts[reason] = (summary.counts[reason] || 0) + 1
    })
  })

  return summary
}

function formatGenerateSummary(updated, summary, creditSuggested, creditOnly) {
  const total = summary.total || 0
  const counts = summary.counts || {}
  const pending = counts.pending_adjustment || 0
  const missing = counts.missing_rent || 0
  const noEligibles = counts.no_eligibles || 0
  const creditOnlySkipped = counts.credit_only || 0
  const accounted = pending + missing + noEligibles + creditOnlySkipped
  const others = Math.max(0, total - accounted)

  const detailsParts = [
    `Ajuste pendiente: ${pending}`,
    `Falta RENT: ${missing}`,
    `Sin elegibles: ${noEligibles}`,
  ]

  if (creditOnlySkipped) {
    detailsParts.push(`Solo créditos: ${creditOnlySkipped}`)
  }

  if (others > 0) {
    detailsParts.push(`Otras: ${others}`)
  }

  const details = total > 0 ? ` — Omitidos ${total} (${detailsParts.join(', ')})` : ''

  return `LQI creadas ${updated} · Con NC sugerida ${creditSuggested} · NCs solas ${creditOnly}${details}`
}

function formatIssueSummary(issued, summary, creditIssued, creditOnlyIssued) {
  const total = summary.total || 0
  const suffix = total > 0 ? ` · Omitidas ${total} (total=0 o validación)` : ''
  const creditPart = ` · NC asociadas ${creditIssued} · NCs solas ${creditOnlyIssued}`
  return `Emitidas ${issued}${creditPart}${suffix}`
}

function formatReopenSummary(reopened, summary) {
  const total = summary.total || 0
  const suffix = total > 0 ? ' (con cobranzas).' : '.'
  return `Reabiertas ${reopened} · Omitidas ${total}${suffix}`
}

function formatPostIssueSummary(result) {
  const summary = summarizeSkipped(result?.skipped)
  const counts = summary.counts || {}
  const pending = counts.pending_adjustment || 0
  const missing = counts.missing_rent || 0
  const nothing = counts.nothing_to_issue || 0
  const missingLqi = counts.missing_lqi_for_adds || 0
  const lqiRequired = counts.lqi_required || 0
  const standaloneNc = counts.standalone_nc_available || 0
  const draftRequires = counts.draft_requires_issue || 0
  const unsupportedStatus = counts.unsupported_status || 0
  const ndCount = result?.nd_issued?.count ?? 0
  const ncCount = result?.nc_issued?.count ?? 0

  const omitParts = [
    `ajustes: ${pending}`,
    `falta RENT: ${missing}`,
    `nada para emitir: ${nothing}`,
  ]

  if (lqiRequired > 0 || missingLqi > 0) {
    omitParts.push(`LQI pendiente: ${lqiRequired + missingLqi}`)
  }

  if (standaloneNc > 0) {
    omitParts.push(`NC sola: ${standaloneNc}`)
  }

  if (draftRequires > 0) {
    omitParts.push(`Draft: ${draftRequires}`)
  }

  if (unsupportedStatus > 0) {
    omitParts.push(`Otros: ${unsupportedStatus}`)
  }

  let message = `ND emitidas: ${ndCount} · NC emitidas: ${ncCount}`
  if (omitParts.length) {
    message += ` · Omitidos — ${omitParts.join(', ')}`
  }

  return message
}

function handleActionError(error, fallback) {
  const message = error?.response?.data?.message || fallback
  snackbar.error(message)
}

function postActionRefresh() {
  tableRef.value?.reload()
  loadKpis()
}

function toggleEligibles() {
  filters.has_eligibles = filters.has_eligibles === true ? null : true
}

function setStatusQuick(status) {
  filters.status = filters.status === status ? null : status
}

function resetQuickFilters() {
  filters.has_eligibles = null
  filters.status = null
}

function currentPeriod() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  return `${now.getFullYear()}-${month}`
}

function formatCoverage(value) {
  if (value === null || value === undefined) {
    return 'N/A'
  }
  return `${value}%`
}
</script>
