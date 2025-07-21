<template>
  <div>
    <h3 class="text-h6 mb-4">Cuenta Corriente</h3>

    <!-- Indicadores de saldo por moneda -->
    <div class="d-flex flex-wrap gap-4 mb-6">
      <v-card
        v-for="balance in balances"
        :key="balance.currency"
        class="balance-card"
        :class="{ 'selected': selectedCurrency === balance.currency }"
        @click="selectCurrency(balance.currency)"
        elevation="2"
        hover
      >
        <v-card-text class="text-center pa-4">
          <div class="d-flex align-center justify-center mb-2">
            <v-icon
              :color="balance.balance >= 0 ? 'success' : 'error'"
              class="me-2"
            >
              {{ balance.balance >= 0 ? 'mdi-arrow-up' : 'mdi-arrow-down' }}
            </v-icon>
            <span class="text-h6 font-weight-bold">
              {{ balance.currency }}
            </span>
          </div>

          <div class="text-h5 font-weight-bold" :class="balance.balance >= 0 ? 'text-success' : 'text-error'">
            {{ formatMoney(balance.balance, balance.currency) }}
          </div>

          <div class="text-caption text-grey-darken-1 mt-1">
            {{ balance.movement_count }} movimiento{{ balance.movement_count !== 1 ? 's' : '' }}
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Tabla de movimientos para la moneda seleccionada -->
    <div v-if="selectedCurrency">
      <AccountMovementTable
        :client-id="clientId"
        :selected-currency="selectedCurrency"
        @currency-change="handleCurrencyChange"
      />
    </div>

    <!-- Mensaje cuando no hay saldos -->
    <div v-else-if="balances.length === 0" class="text-center pa-8">
      <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-account-cash</v-icon>
      <h3 class="text-h6 text-grey-darken-1 mb-2">Sin movimientos</h3>
      <p class="text-body-2 text-grey-darken-2">
        Este cliente no tiene movimientos de cuenta corriente.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/services/axios'
import { formatMoney } from '@/utils/money'
import AccountMovementTable from './AccountMovementTable.vue'

const props = defineProps({
  clientId: Number
})

const balances = ref([])
const selectedCurrency = ref('ARS') // Moneda por defecto
const loading = ref(false)

async function fetchBalances() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/clients/${props.clientId}/account-balances`)
    balances.value = data.balances

    // Si no hay moneda seleccionada y hay balances, seleccionar la primera
    if (balances.value.length > 0 && !selectedCurrency.value) {
      selectedCurrency.value = balances.value[0].currency
    }
  } catch (error) {
    console.error('Error fetching account balances:', error)
  } finally {
    loading.value = false
  }
}

function selectCurrency(currency) {
  selectedCurrency.value = currency
}

function handleCurrencyChange(currency) {
  selectedCurrency.value = currency
}

onMounted(fetchBalances)

// Recargar balances cuando cambie el cliente
watch(() => props.clientId, fetchBalances)
</script>

<style scoped>
.balance-card {
  min-width: 200px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.balance-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.balance-card.selected {
  border: 2px solid var(--v-primary-base);
  background-color: var(--v-primary-lighten5);
}
</style>
