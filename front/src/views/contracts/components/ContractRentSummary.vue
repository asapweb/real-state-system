<template>
  <v-card flat>
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6">Resumen de alquiler</span>
      <v-btn color="primary" @click="generate" :loading="loading" v-if="!voucher">
        Generar resumen
      </v-btn>
    </v-card-title>

    <v-card-text v-if="voucher">
      <div class="mb-2">Período: {{ voucher.period }}</div>
      <v-table density="compact">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Descripción</th>
            <th class="text-end">Importe</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in voucher.items" :key="item.id">
            <td>{{ item.type }}</td>
            <td>{{ item.description }}</td>
            <td class="text-end">{{ formatCurrency(item.unit_price * item.quantity) }}</td>
          </tr>
        </tbody>
      </v-table>

      <div class="text-end text-subtitle-1 mt-2">Total: {{ formatCurrency(voucher.total) }}</div>
    </v-card-text>
  </v-card>
  <v-divider class="my-4" v-if="voucher && voucher.applicationsReceived.length"></v-divider>

<v-card-subtitle v-if="voucher && voucher.applicationsReceived.length">
  Pagos recibidos
</v-card-subtitle>

<v-table density="compact" v-if="voucher && voucher.applicationsReceived.length">
  <thead>
    <tr>
      <th>Recibo</th>
      <th>Fecha</th>
      <th class="text-end">Importe</th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="app in voucher.applicationsReceived" :key="app.id">
      <td>{{ app.voucher?.full_number ?? '—' }}</td>
      <td>{{ formatDate(app.voucher?.issue_date) }}</td>
      <td class="text-end">{{ formatCurrency(app.amount) }}</td>
    </tr>
  </tbody>
</v-table>

<div v-if="voucher" class="text-end text-subtitle-2 mt-2">
  Total abonado: {{ formatCurrency(totalApplied) }} <br />
  Saldo pendiente: {{ formatCurrency(voucher.total - totalApplied) }}
</div>

</template>

<script setup>
import { ref, computed } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps({
  contractId: { type: Number, required: true },
  period: { type: String, required: true }, // ej: "2025-08"
})

const loading = ref(false)
const voucher = ref(null)

const snackbar = useSnackbar()

async function generate() {
  loading.value = true
  try {
    const response = await axios.post(`/api/contracts/${props.contractId}/rent-summary`, {
      period: props.period,
    })
    voucher.value = response.data
    snackbar.success('Resumen de alquiler generado con éxito')
  } catch (error) {
    console.log(error)
    snackbar.error(error.response?.data?.message || 'Error al generar el resumen')
  } finally {
    loading.value = false
  }
}
const totalApplied = computed(() => {
  return (voucher.value?.applications_to_me || []).reduce((sum, app) => {
    return sum + parseFloat(app.amount || 0)
  }, 0)
})

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('es-AR')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
  }).format(value)
}
</script>
