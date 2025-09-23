  <template>
  <v-container fluid class="py-6">
    <!-- Header / Period Selector (simple, limpio) -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="d-flex align-center gap-3">
        <v-btn variant="text" icon @click="goPrevPeriod" :title="'Período anterior'">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <div class="text-h6">{{ periodLabel }}</div>
        <v-select
          v-model="selectedMonth"
          :items="months"
          label="Mes"
          variant="plain"
          hide-details
          density="comfortable"
          style="max-width: 140px"
        />
        <v-select
          v-model="selectedYear"
          :items="years"
          label="Año"
          variant="plain"
          hide-details
          density="comfortable"
          style="max-width: 120px"
        />
        <v-btn variant="text" @click="goToCurrentPeriod">Actual</v-btn>
      </div>

      <!-- Acciones esenciales -->
      <div class="d-flex align-center gap-2">
        <v-btn size="small" variant="tonal" @click="goToAdjustments">Ajustes</v-btn>
        <v-btn size="small" variant="tonal" @click="openGenerateRents">
          Rentas
          <v-badge v-if="kpis.pendingRents" :content="kpis.pendingRents" inline></v-badge>
        </v-btn>
        <v-btn size="small" variant="tonal" @click="openGenerateVouchers">
          Vouchers
          <v-badge v-if="kpis.chargesWithoutVoucher" :content="kpis.chargesWithoutVoucher" inline></v-badge>
        </v-btn>
        <v-btn size="small" variant="tonal" @click="openSendNotices">
          Avisos
          <v-badge v-if="kpis.noticesPending" :content="kpis.noticesPending" inline></v-badge>
        </v-btn>
      </div>
    </div>

    <!-- KPIs minimalistas -->
    <v-row class="mb-4" dense>
      <v-col v-for="card in kpiCards.slice(0,4)" :key="card.key" cols="12" sm="6" md="3">
        <v-sheet class="pa-4" rounded="lg" elevation="0" border>
          <div class="text-caption text-medium-emphasis">{{ card.label }}</div>
          <div class="text-h4 font-weight-bold mt-1">{{ formatNumber(card.value) }}</div>
        </v-sheet>
      </v-col>
    </v-row>

    <v-row>
      <!-- Columna izquierda: pendientes clave en bloques simples -->
      <v-col cols="12" md="7">
        <v-sheet rounded="lg" elevation="0" border class="mb-4">
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <div class="text-subtitle-1">Ajustes pendientes</div>
            <v-btn variant="text" size="small" @click="goToAdjustments">Ver todo</v-btn>
          </div>
          <v-divider />
          <v-data-table
            :items="lists.adjustments"
            :headers="headers.adjustments"
            density="comfortable"
            hover
            hide-default-footer
          />
        </v-sheet>

        <v-sheet rounded="lg" elevation="0" border class="mb-4">
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <div class="text-subtitle-1">Contratos sin renta generada</div>
            <v-btn variant="text" size="small" @click="openGenerateRents">Generar</v-btn>
          </div>
          <v-divider />
          <v-data-table
            :items="lists.missingRents"
            :headers="headers.missingRents"
            density="comfortable"
            hover
            hide-default-footer
          />
        </v-sheet>

        <v-sheet rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <div class="text-subtitle-1">Cargos sin voucher (incluye rentas)</div>
            <v-btn variant="text" size="small" @click="openGenerateVouchers">Generar</v-btn>
          </div>
          <v-divider />
          <v-data-table
            :items="lists.charges"
            :headers="headers.charges"
            density="comfortable"
            hover
            hide-default-footer
          />
        </v-sheet>
      </v-col>

      <!-- Columna derecha: estado y avisos, sin gráficos intrusivos -->
      <v-col cols="12" md="5">
        <v-sheet rounded="lg" elevation="0" border class="mb-4">
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <div class="text-subtitle-1">Vouchers no enviados</div>
            <v-btn variant="text" size="small" @click="openSendNotices">Enviar</v-btn>
          </div>
          <v-divider />
          <v-data-table
            :items="lists.notices"
            :headers="headers.notices"
            density="comfortable"
            hover
            hide-default-footer
          />
        </v-sheet>

        <v-sheet rounded="lg" elevation="0" border>
          <div class="px-4 py-3 text-subtitle-1">Alertas</div>
          <v-divider />
          <v-list density="comfortable">
            <v-list-item v-for="a in alerts" :key="a.id" :title="a.title" :subtitle="a.subtitle" />
          </v-list>
        </v-sheet>
      </v-col>
    </v-row>
  </v-container>
</template>


<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// --- Period state ---
const today = new Date()
const selectedMonth = ref(today.getMonth()) // 0-11
const selectedYear = ref(today.getFullYear())

const months = [
  { title: 'Enero', value: 0 },
  { title: 'Febrero', value: 1 },
  { title: 'Marzo', value: 2 },
  { title: 'Abril', value: 3 },
  { title: 'Mayo', value: 4 },
  { title: 'Junio', value: 5 },
  { title: 'Julio', value: 6 },
  { title: 'Agosto', value: 7 },
  { title: 'Septiembre', value: 8 },
  { title: 'Octubre', value: 9 },
  { title: 'Noviembre', value: 10 },
  { title: 'Diciembre', value: 11 },
]
const currentYear = today.getFullYear()
const years = Array.from({ length: 7 }).map((_, i) => currentYear - 3 + i)

const periodLabel = computed(() => {
  const m = months.find(m => m.value === selectedMonth.value)?.title || ''
  return `${m} ${selectedYear.value}`
})

function goPrevPeriod() {
  const d = new Date(selectedYear.value, selectedMonth.value, 1)
  d.setMonth(d.getMonth() - 1)
  selectedMonth.value = d.getMonth()
  selectedYear.value = d.getFullYear()
}
function goToCurrentPeriod() {
  selectedMonth.value = today.getMonth()
  selectedYear.value = today.getFullYear()
}

// --- KPIs (placeholder data; wireframe only) ---
const kpis = ref({
  activeContracts: 124,
  pendingAdjustments: 18,
  pendingRents: 42,
  chargesWithoutVoucher: 23,
  noticesPending: 15,
  expiringSoon: 7,
  vouchersWithIssues: 4,
})

const kpiCards = computed(() => [
  { key: 'activeContracts', label: 'Contratos activos', value: kpis.value.activeContracts, icon: 'mdi-home-city', color: 'primary' },
  { key: 'pendingAdjustments', label: 'Pendientes de ajuste', value: kpis.value.pendingAdjustments, icon: 'mdi-tune', color: 'secondary' },
  { key: 'pendingRents', label: 'Pendientes de generar renta', value: kpis.value.pendingRents, icon: 'mdi-cash', color: 'success' },
  { key: 'chargesWithoutVoucher', label: 'Cargos sin voucher', value: kpis.value.chargesWithoutVoucher, icon: 'mdi-file-document-outline', color: 'warning' },
  { key: 'noticesPending', label: 'Avisos sin enviar', value: kpis.value.noticesPending, icon: 'mdi-send', color: 'info' },
  { key: 'expiringSoon', label: 'Próximos a expirar', value: kpis.value.expiringSoon, icon: 'mdi-timer-sand', color: 'error' },
  { key: 'vouchersWithIssues', label: 'Vouchers en revisión', value: kpis.value.vouchersWithIssues, icon: 'mdi-alert-circle', color: 'error' },
])

function formatNumber(n) {
  return new Intl.NumberFormat('es-AR').format(n)
}

// --- Lists (wireframe) ---
const lists = ref({
  adjustments: [
    { contract: 'ALQ-0012', due: '10/09/2025', type: 'IPC', status: 'pendiente' },
    { contract: 'ALQ-0341', due: '15/09/2025', type: 'ICL', status: 'pendiente' },
  ],
  missingRents: [
    { contract: 'ALQ-0203', tenant: 'García S.A.', month: 'Sep 2025' },
    { contract: 'ALQ-0457', tenant: 'López Juan', month: 'Sep 2025' },
  ],
  notices: [
    { voucher: 'COB-0001-00002341', tenant: 'Martínez Ana', method: 'Email', status: 'no enviado' },
  ],
  charges: [
    { contract: 'ALQ-0203', item: 'Renta', currency: 'ARS', amount: 1500000 },
    { contract: 'ALQ-0203', item: 'Expensas', currency: 'ARS', amount: 120000 },
  ],
})

const headers = {
  adjustments: [
    { title: 'Contrato', key: 'contract' },
    { title: 'Vence', key: 'due' },
    { title: 'Índice', key: 'type' },
    { title: 'Estado', key: 'status' },
  ],
  missingRents: [
    { title: 'Contrato', key: 'contract' },
    { title: 'Inquilino', key: 'tenant' },
    { title: 'Período', key: 'month' },
  ],
  notices: [
    { title: 'Comprobante', key: 'voucher' },
    { title: 'Inquilino', key: 'tenant' },
    { title: 'Canal', key: 'method' },
    { title: 'Estado', key: 'status' },
  ],
  charges: [
    { title: 'Contrato', key: 'contract' },
    { title: 'Ítem', key: 'item' },
    { title: 'Moneda', key: 'currency' },
    { title: 'Importe', key: 'amount', value: (v) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(v.amount) },
  ],
}

// --- Actions (navigation placeholders) ---
function goToAdjustments() {
  router.push({ path: '/contracts/adjustments', query: { month: selectedMonth.value + 1, year: selectedYear.value } })
}
function openGenerateRents() {
  // Navega a la pantalla existente de generación de rentas / cuotas
  router.push({ path: '/contracts/rents/generation', query: { month: selectedMonth.value + 1, year: selectedYear.value } })
}
function openGenerateVouchers() {
  // Navega a la pantalla existente de generación de vouchers (COB/LIQ/FAC/ND/NC)
  router.push({ path: '/contracts/vouchers/generation', query: { month: selectedMonth.value + 1, year: selectedYear.value } })
}
function openSendNotices() {
  router.push({ path: '/vouchers/notices', query: { month: selectedMonth.value + 1, year: selectedYear.value } })
}
function refresh() {
  // Placeholder: Aquí se dispararían fetchers a la API para el período seleccionado
}

// React to period change (wireframe)
watch([selectedMonth, selectedYear], () => {
  refresh()
})
</script>

<style scoped>
.kpi-card {
  background: linear-gradient(180deg, rgba(250,250,252,1) 0%, rgba(245,245,250,1) 100%);
}
.chart-placeholder {
  height: 220px;
  border: 1px dashed var(--v-theme-outline-variant);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}
/* Small gap utility */
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.gap-4 { gap: 16px; }
</style>
