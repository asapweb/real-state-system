<template>
  <!-- Los movimientos se muestran en orden cronológico (más antiguos primero) -->
  <div>
    <!-- Mostrar tabla solo si hay moneda seleccionada -->
    <div v-if="selectedCurrency">
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="total"
        :loading="loading"
        :items-per-page="perPage"
        :page="page"
        @update:page="onPageChange"
        @update:items-per-page="onPerPageChange"
      >
        <template #item.date="{ item }">
          {{ item.date ? new Date(item.date).toLocaleDateString() : '' }}
        </template>

        <template #item.voucher_formatted_number="{ item }">
          {{ item.voucher_formatted_number || '-' }}
        </template>

        <template #item.description="{ item }">
          {{ item.description || '-' }}
        </template>

        <template #item.debit="{ item }">
          <span v-if="item.amount< 0" class="text-red">
            {{ formatMoney(item.amount, item.currency) }}
          </span>
        </template>

        <template #item.credit="{ item }">
          <span v-if="item.amount > 0" class="text-green">
            {{ formatMoney(Math.abs(item.amount), item.currency) }}
          </span>
        </template>

        <template #item.running_balance="{ item }">
          <span :class="item.running_balance >= 0 ? 'text-green' : 'text-red'">
            {{ formatMoney(item.running_balance, item.currency) }}
          </span>
        </template>

        <!-- Mensaje cuando no hay movimientos -->
        <template #no-data>
          <div class="text-center pa-8">
            <v-icon size="48" color="grey-lighten-1" class="mb-4">mdi-account-cash</v-icon>
            <h3 class="text-h6 text-grey-darken-1 mb-2">No hay movimientos</h3>
            <p class="text-body-2 text-grey-darken-2">
              No se encontraron movimientos de cuenta corriente en {{ selectedCurrency }}.
            </p>
          </div>
        </template>
      </v-data-table-server>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/services/axios'
import { formatMoney } from '@/utils/money'

const props = defineProps({
  clientId: Number,
  selectedCurrency: String
})

const headers = [
  { title: 'Fecha', key: 'date', width: '120px' },
  { title: 'Comprobante', key: 'voucher_formatted_number', width: '150px' },
  { title: 'Descripción', key: 'description' },
  { title: 'Debe', key: 'debit', align: 'end', width: '120px' },
  { title: 'Haber', key: 'credit', align: 'end', width: '120px' },
  { title: 'Saldo', key: 'running_balance', align: 'end', width: '120px' },
]

const items = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)

async function fetchData() {
  // No hacer la petición si no hay moneda seleccionada
  if (!props.selectedCurrency) {
    items.value = []
    total.value = 0
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get(`/api/clients/${props.clientId}/account-movements`, {
      params: {
        page: page.value,
        per_page: perPage.value,
        currency: props.selectedCurrency,
      },
    })
    items.value = data.data
    total.value = data.meta.total
  } catch (error) {
    console.error('Error fetching account movements:', error)
    if (error.response?.status === 400) {
      // Error de moneda requerida
      items.value = []
      total.value = 0
    }
  } finally {
    loading.value = false
  }
}

function onPageChange(p) {
  page.value = p
  fetchData()
}

function onPerPageChange(p) {
  perPage.value = p
  fetchData()
}

onMounted(fetchData)
watch(() => props.selectedCurrency, fetchData)
</script>
