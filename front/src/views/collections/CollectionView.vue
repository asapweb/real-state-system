<template>
  <div>
    <h2 class="mb-4">
      Cobranza - {{ contract?.main_tenant?.client?.full_name }} ({{ period }})
    </h2>

    <!-- Información del contrato -->
    <v-card class="mb-4">
      <v-card-text>
        <div><strong>Contrato:</strong> CON-{{ contract?.id }}</div>
        <div><strong>Inquilino:</strong> {{ contract?.main_tenant?.client?.full_name }}</div>
        <div><strong>Período:</strong> {{ period }}</div>
        <div>
          <strong>Estado:</strong>
          <v-chip size="small" :color="statusColor(status)" class="ml-2">
            {{ statusLabel(status) }}
          </v-chip>
        </div>
      </v-card-text>
    </v-card>

    <!-- Vouchers -->
    <v-card class="mb-4" v-for="voucher in vouchers" :key="voucher.id">
      <v-card-title class="d-flex justify-space-between align-center">
        <div>
          <v-icon start class="me-2">mdi-file-document</v-icon>
          Voucher {{ voucher.voucher_type_short_name }} {{ voucher.voucher_type_letter }} {{ voucher.full_number }}
        </div>
        <v-chip size="small" :color="voucher.status === 'issued' ? 'success' : 'warning'">
          {{ voucher.status === 'issued' ? 'Emitido' : 'Borrador' }}
        </v-chip>
      </v-card-title>
      <v-card-text>
        <p><strong>Moneda:</strong> {{ voucher.currency }}</p>
        <p><strong>Total:</strong> {{ formatMoney(voucher.total, voucher.currency) }}</p>

        <!-- Ítems -->
        <v-data-table
          :headers="itemHeaders"
          :items="voucher.items"
          class="mb-4"
          density="compact"
          hide-default-footer
        >
          <template #[`item.subtotal`]="{ item }">
            {{ formatMoney(item.subtotal, voucher.currency) }}
          </template>
        </v-data-table>

        <!-- Pagos / Aplicaciones -->
        <div v-if="voucher.payments?.length">
          <h4>Pagos</h4>
          <ul>
            <li v-for="payment in voucher.payments" :key="payment.id">
              {{ formatMoney(payment.amount, voucher.currency) }} - {{ payment.method }}
            </li>
          </ul>
        </div>
        <div v-if="voucher.applications?.length">
          <h4>Aplicaciones</h4>
          <ul>
            <li v-for="app in voucher.applications" :key="app.id">
              Aplicado a {{ app.applied_to_id }}: {{ formatMoney(app.amount, voucher.currency) }}
            </li>
          </ul>
        </div>
      </v-card-text>
    </v-card>

    <!-- Botón volver -->
    <div class="d-flex justify-end">
      <v-btn color="grey" variant="outlined" @click="$router.push('/collections')">
        Volver al listado
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatMoney } from '@/utils/money'
import { statusLabel, statusColor } from '@/utils/collections-formatter'

const props = defineProps({
  contractId: { type: Number, required: true },
  period: { type: String, required: true },
})

const snackbar = useSnackbar()
const contract = ref(null)
const vouchers = ref([])
const status = ref('pending')

const itemHeaders = [
  { title: 'Descripción', key: 'description', sortable: false },
  { title: 'Cantidad', key: 'quantity', sortable: false },
  { title: 'Precio unitario', key: 'unit_price', sortable: false },
  { title: 'Subtotal', key: 'subtotal', sortable: false },
]

onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/collections/${props.contractId}/view`, {
      params: { period: '2025-08' },
    })

    contract.value = data.contract
    vouchers.value = data.vouchers
    status.value = data.status
  } catch (err) {
    snackbar.error('Error al cargar la cobranza.')
  }
})
</script>
