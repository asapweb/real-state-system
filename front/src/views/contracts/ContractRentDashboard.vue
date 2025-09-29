<template>
  <!-- Header -->
  <div class="mb-2">
    
    <h2 class="text-h5">Gestion de Alquileres</h2>
    <PeriodNavigation v-model="periodInput" />
  </div>
  <v-row dense>
    <v-col cols="12" sm="6" md="3" class="flex-grow-1">
      <KpiCard
        title="Contratos activos"
        tooltip="Contratos en estado activo durante el período"
        :value="summary.totals.contracts_active || 0"
        :loading="loading.summary"
        @click="goToContracts"
      />
    </v-col>
    <v-col cols="12" sm="6" md="3" class="flex-grow-1">
      <KpiCard
        title="Ajuste pendiente"
        tooltip="Ajustes del período sin aplicar"
        :value="summary.totals.adjustments?.pending || 0"
        :total="summary.totals.adjustments?.scheduled || 0"
        :loading="loading.summary"
        @click="goToAdjustments"
      />

    </v-col>
    <v-col cols="12" sm="6" md="3" class="flex-grow-1">
      <KpiCard
        title="Cuota pendiente"
        tooltip="Contratos sin cuota generada"
        :value="summary.totals.rent?.missing || 0"
        :total="summary.totals.rent?.expected || 0"
        :loading="loading.summary"
        @click="goToRent"
      />
    </v-col>
    <v-col cols="12" sm="6" md="3"class="flex-grow-1">
      <KpiCard
        title="Liq. Inq. a generar"
        tooltip="Contratos con cargos sin liquidar al inquilino"
        :value="summary.totals.lqi?.to_generate || 0"
        :loading="loading.summary"
        @click="goToVoucher"
      />
    </v-col>
  </v-row>







</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatDate, formatPeriodWithMonthName } from '@/utils/date-formatter'
import KpiCard from '@/views/components/KpiCard.vue'
import PeriodNavigation from '@/views/components/PeriodNavigation.vue'
const { success, error } = useSnackbar()
const showError = (m) => error(m)

const router = useRouter()

// Period yyyy-MM for inputs and queries
const now = new Date()
const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const periodInput = ref(defaultMonth)
const period = computed(() => periodInput.value || null)

// Summary (Step 1)
const summary = reactive({ period: null, totals: {} })
const loading = reactive({ summary: false, list: false })


const goToContracts = () => {
  router.push({ name: 'contract.index' })
}

const goToAdjustments = () => {
  router.push({ name: 'contract.adjustments.index', query: { period: period.value } })
}

const goToRent = () => {
  router.push({ name: 'contracts.rents' })
}

const goToVoucher = () => {
  router.push({ name: 'contracts.rents.voucher' })
}

// Watch filters and period
// watch(() => ({ ...filters, period: period.value }), triggerFetchList, { deep: true })
watch(period, async () => {
  // resetSelection()
  await fetchSummary()
  // triggerFetchList()
})

const resetSelection = () => { selected.value = [] }

// Fetch summary
const fetchSummary = async () => {
  if (!period.value) return
  loading.summary = true
  try {
    const { data } = await axios.get('/api/dashboard/summary', { params: { period: period.value } })
    summary.period = data.period
    summary.totals = data.totals || {}
  } catch (e) {
    console.error(e)
    showError('Error al cargar el resumen')
  } finally {
    loading.summary = false
  }
}

onMounted(async () => {
  await fetchSummary()
})
</script>
