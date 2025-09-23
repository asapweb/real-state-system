<template>
  <div class="d-flex flex-column gap-4">
    <!-- Header / Toolbar -->
    <v-card>
      <v-toolbar density="comfortable">
        <v-toolbar-title>Generación de vouchers</v-toolbar-title>
        <v-spacer />
        <v-text-field
          v-model="periodInput"
          type="month"
          label="Período"
          hide-details
          density="comfortable"
          style="max-width: 160px"
          class="me-3"
        />
        <v-btn
          color="primary"
          :loading="loadingAction"
          :disabled="!period"
          @click="handleGenerate"
        >
          <v-icon start>mdi-file-document-edit</v-icon>
          Generar/Sincronizar
        </v-btn>
      </v-toolbar>
      <v-progress-linear v-if="loadingOverview" indeterminate color="primary" />

      <v-card-text>
        <v-row class="align-center">
          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis">Período</div>
            <div class="text-h6">{{ formatPeriodWithMonthName(periodInput) }}</div>
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis">Contratos activos</div>
            <div class="text-h6">{{ overview?.active_contracts ?? 0 }}</div>
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis d-flex align-center">
              Ajustes pendientes
              <v-tooltip v-if="(overview?.blocked_adjustments || 0) > 0" text="Hay ajustes bloqueados que pueden impedir generar COB" location="top">
                <template #activator="{ props }">
                  <v-icon v-bind="props" size="18" color="warning" class="ms-1">mdi-alert-outline</v-icon>
                </template>
              </v-tooltip>
            </div>
            <div class="text-h6">{{ overview?.blocked_adjustments ?? 0 }}</div>
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis d-flex align-center">
              Rentas faltantes
              <v-tooltip v-if="(overview?.missing_rent || 0) > 0" text="Existen rentas no generadas para el período" location="top">
                <template #activator="{ props }">
                  <v-icon v-bind="props" size="18" color="warning" class="ms-1">mdi-alert-outline</v-icon>
                </template>
              </v-tooltip>
            </div>
            <div class="text-h6">{{ overview?.missing_rent ?? 0 }}</div>
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis">COB en borrador</div>
            <div class="text-h6">{{ overview?.draft_cob_count ?? 0 }}</div>
          </v-col>

          <v-col cols="12" md="12">
            <div class="text-caption text-medium-emphasis mb-1">Pendientes por tipo</div>
            <div class="d-flex flex-wrap gap-2">
              <v-chip size="small" color="primary" variant="flat">
                RENT: {{ overview?.pending_by_type?.RENT?.count || 0 }} · {{ formatMoney(overview?.pending_by_type?.RENT?.total || 0, 'ARS') }}
              </v-chip>
              <v-chip size="small" color="secondary" variant="flat">
                OWNER_TO_TENANT: {{ overview?.pending_by_type?.OWNER_TO_TENANT?.count || 0 }} · {{ formatMoney(overview?.pending_by_type?.OWNER_TO_TENANT?.total || 0, 'ARS') }}
              </v-chip>
              <v-chip size="small" color="indigo" variant="flat">
                AGENCY_TO_TENANT: {{ overview?.pending_by_type?.AGENCY_TO_TENANT?.count || 0 }} · {{ formatMoney(overview?.pending_by_type?.AGENCY_TO_TENANT?.total || 0, 'ARS') }}
              </v-chip>
              <v-chip size="small" color="grey" variant="flat">
                Falta PAG_INT (AGENCY): {{ overview?.pending_by_type?.AGENCY_TO_TENANT?.missing_pag_int || 0 }}
              </v-chip>
              <v-chip size="small" color="green" variant="flat">
                BONIFICATION: {{ overview?.pending_by_type?.BONIFICATION?.count || 0 }} · {{ formatMoney(overview?.pending_by_type?.BONIFICATION?.total || 0, 'ARS') }}
              </v-chip>
            </div>
          </v-col>
        </v-row>

        <v-alert
          v-if="(overview?.blocked_adjustments || 0) > 0 || (overview?.missing_rent || 0) > 0"
          type="warning"
          variant="tonal"
          density="comfortable"
          class="mt-4"
        >
          Existen ajustes bloqueados y/o rentas faltantes. Puedes revisar en
          <RouterLink to="/contracts/rents" target="_blank">Rentas</RouterLink>
          o en
          <RouterLink to="/contracts/adjustments" target="_blank">Ajustes</RouterLink>.
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Opciones de generación -->
    <v-card>
      <v-card-text>
        <v-row class="align-center" dense>
          <v-col cols="12" md="4">
            <v-select
              v-model="options.create_or_sync"
              :items="createOrSyncItems"
              item-title="text"
              item-value="value"
              label="Modo"
              hide-details
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch
              v-model="options.require_pag_int"
              label="Requerir PAG_INT para AGENCY_TO_TENANT"
              hide-details
              color="primary"
              density="comfortable"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch
              v-model="options.include_bonifications"
              label="Incluir bonificaciones en draft"
              hide-details
              color="primary"
              density="comfortable"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabla server-side -->
    <v-card>
      <v-card-title class="text-h6 d-flex align-center">
        Contratos activos con pendientes a COB
        <v-spacer />
        <div class="text-caption text-medium-emphasis me-3">
          Seleccionados: {{ selected.length }}
        </div>
        <v-btn color="primary" :loading="loadingAction" :disabled="!period" @click="handleGenerate">
          <v-icon start>mdi-file-document-edit</v-icon> Generar/Sincronizar
        </v-btn>
      </v-card-title>

      <v-data-table-server
        v-model:items-per-page="tableOptions.itemsPerPage"
        v-model:sort-by="tableOptions.sortBy"
        v-model:page="tableOptions.page"
        v-model="selected"
        :headers="headers"
        :items="items"
        :items-length="total"
        :loading="loadingTable"
        item-value="contract_id"
        class="elevation-1"
        show-select
        return-object
        @update:options="fetchList"
      >
        <template #[`item.tenant_name`]="{ item }">
          <div class="d-flex flex-column">
            <strong>{{ item.tenant_name }}</strong>
            <small class="text-medium-emphasis">CON {{ item.contract_id }}</small>
          </div>
        </template>

        <template #[`item.rent_amount`]="{ item }">
          <div class="text-right">{{ formatMoney(item.rent_amount, item.currency) }}</div>
        </template>

        <template #[`item.pending`]="{ item }">
          <div class="d-flex justify-end align-center gap-2">
            <v-chip size="small" variant="tonal">{{ item.pending_charges_count }}</v-chip>
            <div class="text-right">{{ formatMoney(item.pending_charges_total, item.currency) }}</div>
          </div>
        </template>

        <template #[`item.draft_voucher_id`]="{ item }">
          <v-chip v-if="item.draft_voucher_id" size="small" color="primary" variant="tonal">
            #{{ item.draft_voucher_id }}
          </v-chip>
          <span v-else class="text-disabled">—</span>
        </template>

        <template #[`item.issues`]="{ item }">
          <div class="d-flex flex-wrap gap-2">
            <v-tooltip
              v-for="issue in (item.issues || [])"
              :key="issue"
              :text="issueTooltip(issue)"
              location="top"
            >
              <template #activator="{ props }">
                <v-chip
                  v-bind="props"
                  size="x-small"
                  :color="issueColor(issue)"
                  variant="tonal"
                >
                  {{ issueLabel(issue) }}
                </v-chip>
              </template>
            </v-tooltip>
            <span v-if="!item.issues || item.issues.length === 0" class="text-disabled">—</span>
          </div>
        </template>
      </v-data-table-server>
    </v-card>
  </div>

</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { RouterLink } from 'vue-router'
import { formatMoney } from '@/utils/money'

// Snackbar
const snackbar = useSnackbar()

// Period handling
const periodInput = ref(defaultMonth()) // yyyy-MM
const period = computed(() => (periodInput.value ? `${periodInput.value}-01` : null))

// Overview
const overview = ref(null)
const loadingOverview = ref(false)

// Options
const options = ref({
  create_or_sync: 'sync',
  require_pag_int: true,
  include_bonifications: true,
})

const createOrSyncItems = [
  { text: 'Sincronizar borradores (recomendado)', value: 'sync' },
  { text: 'Crear nuevos vouchers', value: 'create' },
]

// Table state
const items = ref([])
const total = ref(0)
const loadingTable = ref(false)
const selected = ref([]) // array de objetos (return-object)
const tableOptions = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'tenant_name', order: 'asc' }],
})

const headers = [
  { title: 'Contrato / Inquilino', key: 'tenant_name', sortable: true },
  { title: 'Propiedad', key: 'property_label', sortable: true },
  { title: 'Moneda', key: 'currency', sortable: true },
  { title: 'Renta del período', key: 'rent_amount', sortable: true, align: 'end' },
  { title: 'Pendientes a COB', key: 'pending', sortable: false, align: 'end' },
  { title: 'Draft COB', key: 'draft_voucher_id', sortable: false },
  { title: 'Issues', key: 'issues', sortable: false },
]

// Action state
const loadingAction = ref(false)
const lastSummary = ref(null)

onMounted(() => {
  fetchOverview()
  fetchList()
})

watch(period, () => {
  // Reset page when period changes
  tableOptions.value.page = 1
  fetchOverview()
  fetchList()
})

watch(options, () => {
  // Options may affect listing in the future; refresh to be safe
  tableOptions.value.page = 1
  fetchList()
}, { deep: true })

async function fetchOverview() {
  if (!period.value) return
  loadingOverview.value = true
  try {
    const { data } = await axios.get('/api/contracts/vouchers/overview', {
      params: { period: period.value },
    })
    overview.value = data
  } catch (e) {
    snackbar.error(e?.response?.data?.message || 'Error al cargar overview')
  } finally {
    loadingOverview.value = false
  }
}

async function fetchList() {
  if (!period.value) return
  loadingTable.value = true
  try {
    const sortKey = tableOptions.value.sortBy?.[0]?.key
    const sortDir = tableOptions.value.sortBy?.[0]?.order
    const { data } = await axios.get('/api/contracts/vouchers/list', {
      params: {
        period: period.value,
        per_page: tableOptions.value.itemsPerPage,
        page: tableOptions.value.page,
        sort_by: sortKey,
        sort_direction: sortDir,
      },
    })
    items.value = data.data || []
    total.value = data.meta?.total || (data.data ? data.data.length : 0)
  } catch (e) {
    snackbar.error(e?.response?.data?.message || 'Error al cargar listado')
  } finally {
    loadingTable.value = false
  }
}

  async function handleGenerate() {
    if (!period.value) return
    const selectedIds = selected.value.map((row) => row.contract_id)
    const contractIds = selectedIds.length > 0 ? selectedIds : items.value.map((i) => i.contract_id)

    if (!contractIds || contractIds.length === 0) {
      snackbar.info('No hay contratos para procesar en la página actual')
      return
    }

    loadingAction.value = true
    try {
      const body = {
        period: period.value,
        contract_ids: contractIds,
        options: {
          create_or_sync: options.value.create_or_sync,
          require_pag_int: options.value.require_pag_int,
          include_bonifications: options.value.include_bonifications,
          dry_run: false,
        },
      }

      const { data } = await axios.post('/api/contracts/vouchers/generate', body)
      lastSummary.value = data?.summary || null

      const s = data?.summary || {}
      // Mensaje principal
      snackbar.success(`Procesados: ${s.processed || 0} · Creados: ${s.created || 0} · Sincronizados: ${s.synced || 0} · Omitidos: ${s.skipped || 0}`)
      // Advertencias de bloqueos
      const warnings = []
      if ((s.blocked_adjustment || 0) > 0) warnings.push(`Ajustes bloqueados: ${s.blocked_adjustment}`)
      if ((s.missing_rent || 0) > 0) warnings.push(`Rentas faltantes: ${s.missing_rent}`)
      if ((s.agency_missing_pag || 0) > 0) warnings.push(`AGENCY sin PAG_INT: ${s.agency_missing_pag}`)
      if (warnings.length) snackbar.warning(warnings.join(' · '))
      // Errores con detalle corto
      if ((s.errors || 0) > 0) {
        const errors = (data?.results || []).filter(r => r.status === 'error')
        const sample = errors.slice(0, 2).map(e => `CON ${e.contract_id}: ${(e.notes && e.notes[0]) || 'Error'}`)
        snackbar.error([`Errores: ${s.errors}`, ...sample].join(' · '))
      }

      // Refresh data and clear selection
      await Promise.all([fetchOverview(), fetchList()])
      selected.value = []
    } catch (e) {
      snackbar.error(e?.response?.data?.message || 'Error al generar/sincronizar')
    } finally {
      loadingAction.value = false
    }
  }

// Helpers
function defaultMonth() {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

function formatPeriodWithMonthName(yyyyMm) {
  if (!yyyyMm) return '—'
  const [y, m] = yyyyMm.split('-')
  const date = new Date(Number(y), Number(m) - 1, 1)
  return date.toLocaleDateString('es-AR', { year: 'numeric', month: 'long' })
}

function issueLabel(val) {
  const map = {
    BLOCKED_ADJUSTMENT: 'Ajuste bloqueado',
    MISSING_RENT: 'Renta faltante',
    AGENCY_MISSING_PAG: 'Falta PAG_INT',
  }
  return map[val] || val
}

function issueTooltip(val) {
  const map = {
    BLOCKED_ADJUSTMENT: 'El contrato tiene ajustes pendientes/bloqueados para el período',
    MISSING_RENT: 'No se generó la renta del período',
    AGENCY_MISSING_PAG: 'Falta registrar el egreso correspondiente (PAG_INT) en agencia',
  }
  return map[val] || val
}

function issueColor(val) {
  const map = {
    BLOCKED_ADJUSTMENT: 'warning',
    MISSING_RENT: 'warning',
    AGENCY_MISSING_PAG: 'orange',
  }
  return map[val] || 'grey'
}
</script>

<style scoped>
.gap-2 { gap: 8px; }
.text-right { text-align: right; }
</style>
