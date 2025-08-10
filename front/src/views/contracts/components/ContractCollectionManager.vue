<template>
  <v-card flat>
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6">Gestión de Cobranzas</span>
      <v-btn color="primary" @click="generateVouchers" :disabled="selectedItems.length === 0 || loading" :loading="loading">
        Generar comprobantes
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-table density="compact">
        <thead>
          <tr>
            <th></th>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Período</th>
            <th>Monto sugerido</th>
            <th>Monto final</th>
            <th>Moneda</th>
            <th>Comprobante actual</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <v-checkbox
                v-model="selectedItems"
                :value="item"
                :disabled="item.locked"
                density="compact"
              />
            </td>
            <td>{{ item.type }}</td>
            <td>{{ item.description }}</td>
            <td>{{ item.period }}</td>
            <td class="text-end">{{ formatCurrency(item.suggested_amount, item.currency) }}</td>
            <td class="text-end">
              <v-text-field
                v-model.number="item.final_amount"
                type="number"
                density="compact"
                hide-details
                single-line
                class="text-right"
                :suffix="item.currency"
                :readonly="item.locked"
              />
            </td>
            <td>{{ item.currency }}</td>
            <td>{{ item.voucher_full_number || '-' }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps({
  contractId: Number,
  period: String, // formato YYYY-MM
})

const items = ref([])
const selectedItems = ref([])
const loading = ref(false)
const { showSnackbar } = useSnackbar()

function formatCurrency(amount, currency) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(amount)
}

async function loadPendingItems() {
  const response = await axios.get(`/api/contracts/${props.contractId}/pending-charges`, {
    params: { period: props.period },
  })
  items.value = response.data.map(i => ({
    ...i,
    final_amount: i.suggested_amount,
  }))
}

async function generateVouchers() {
  try {
    loading.value = true
    await axios.post(`/api/contracts/${props.contractId}/generate-vouchers`, {
      period: props.period,
      items: selectedItems.value.map(i => ({
        id: i.id,
        type: i.type,
        amount: i.final_amount,
      }))
    })
    showSnackbar('Comprobantes generados con éxito', 'success')
    await loadPendingItems()
    selectedItems.value = []
  } catch (e) {
    showSnackbar('Error al generar comprobantes', 'error')
  } finally {
    loading.value = false
  }
}

loadPendingItems()
</script>
