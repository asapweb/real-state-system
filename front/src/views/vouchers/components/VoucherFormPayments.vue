<template>
  <h2 class="text-h6">
    Pagos
    <v-btn
      prepend-icon="mdi-plus"
      size="small"
      color="primary"
      variant="text"
      class="ml-2"
      @click="showCreationRow = true"
      v-if="!showCreationRow"
    >
      Agregar pago
    </v-btn>
  </h2>

  <v-table striped="even" class="no-borders">
    <thead>
      <tr>
        <th class="font-weight-bold text-start w-25">Método de Pago</th>
        <th class="font-weight-bold text-start w-25">Cuenta de Caja</th>
        <th class="font-weight-bold text-end w-10">Monto</th>
        <th class="font-weight-bold text-start">Referencia</th>
        <th class="font-weight-bold text-center w-1">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <!-- Pagos existentes -->
      <tr v-for="(payment, index) in form.payments" :key="index">
        <td>{{ getPaymentMethodName(payment.payment_method_id) }}</td>
        <td>{{ getCashAccountName(payment.cash_account_id) }}</td>
        <td class="text-end">$ {{ formatCurrency(payment.amount) }}</td>
        <td>{{ payment.reference || '-' }}</td>
        <td class="text-center">
          <v-btn icon="mdi-delete" variant="text" size="x-small" color="error" @click="removePayment(index)" />
        </td>
      </tr>

      <!-- Fila de nuevo pago -->
      <tr v-if="showCreationRow">
        <td>
          <v-autocomplete
            v-model="newPayment.payment_method_id"
            :items="paymentMethods"
            item-title="name"
            item-value="id"
            density="compact"
            variant="underlined"
            hide-details
            placeholder="Método de Pago"
            :loading="loadingPaymentMethods"
            clearable
          />
        </td>
        <td>
          <v-autocomplete
            v-model="newPayment.cash_account_id"
            :items="cashAccounts"
            item-title="name"
            item-value="id"
            density="compact"
            variant="underlined"
            hide-details
            placeholder="Cuenta de Caja"
            :loading="loadingCashAccounts"
            clearable
          />
        </td>
        <td>
          <v-text-field
            v-model="newPayment.amount"
            type="number"
            step="0.01"
            density="compact"
            variant="underlined"
            hide-details
            prefix="$"
            class="text-right-field"
          />
        </td>
        <td>
          <v-text-field
            v-model="newPayment.reference"
            density="compact"
            variant="underlined"
            hide-details
            placeholder="Referencia"
            :required="selectedPaymentMethod?.requires_reference"
          />
        </td>
        <td class="text-center" nowrap>
          <v-btn
            icon="mdi-check"
            size="x-small"
            color="primary"
            variant="text"
            :disabled="!canAddPayment"
            @click="addPayment"
          />
          <v-btn
            icon="mdi-close"
            size="x-small"
            color="grey"
            variant="text"
            @click="cancelPayment"
          />
        </td>
      </tr>
    </tbody>
  </v-table>

  <!-- Totales -->
  <v-row v-if="form.payments?.length" class="mt-4">
    <v-col cols="12" md="6" offset-md="6">
      <v-container>
        <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
          <v-col cols="auto" class="text-right pr-2">
            <span>Total Pagado:</span>
          </v-col>
          <v-col cols="auto" class="text-right">
            <span>$ {{ formatCurrency(paymentsTotal) }}</span>
          </v-col>
        </v-row>
        <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
          <v-col cols="auto" class="text-right pr-2">
            <span>Total Voucher:</span>
          </v-col>
          <v-col cols="auto" class="text-right">
            <span>$ {{ formatCurrency(form.total || 0) }}</span>
          </v-col>
        </v-row>
        <v-row no-gutters class="justify-end mb-1 font-weight-medium" style="font-size: 1.1rem;">
          <v-col cols="auto" class="text-right pr-2">
            <span>Diferencia:</span>
          </v-col>
          <v-col cols="auto" class="text-right">
            <span :class="paymentDifference >= 0 ? 'text-success' : 'text-error'">
              $ {{ formatCurrency(paymentDifference) }}
            </span>
          </v-col>
        </v-row>
      </v-container>
    </v-col>
  </v-row>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue'

const props = defineProps({
  form: Object,
  paymentMethods: Array,
  cashAccounts: Array,
  loadingPaymentMethods: Boolean,
  loadingCashAccounts: Boolean,
})

const emit = defineEmits(['creationRowChanged', 'itemsUpdated'])

const showCreationRow = ref(false)

// Emitir cambios en showCreationRow
watch(showCreationRow, (newValue) => {
  emit('creationRowChanged', newValue)
})

const newPayment = reactive({
  payment_method_id: null,
  cash_account_id: null,
  amount: 0,
  reference: '',
})

const selectedPaymentMethod = computed(() => {
  return props.paymentMethods.find(pm => pm.id === newPayment.payment_method_id)
})

const canAddPayment = computed(() =>
  newPayment.payment_method_id &&
  newPayment.cash_account_id &&
  newPayment.amount > 0
)

const paymentsTotal = computed(() => {
  return props.form.payments.reduce((total, p) => total + Number(p.amount || 0), 0)
})

const paymentDifference = computed(() => {
  return (props.form.total || 0) - paymentsTotal.value
})

function addPayment() {
  props.form.payments.push({ ...newPayment })
  emit('itemsUpdated')
  resetNewPayment()
  showCreationRow.value = false
}

function cancelPayment() {
  resetNewPayment()
  showCreationRow.value = false
}

function resetNewPayment() {
  newPayment.payment_method_id = null
  newPayment.cash_account_id = null
  newPayment.amount = 0
  newPayment.reference = ''
}

function removePayment(index) {
  props.form.payments.splice(index, 1)
}

function getPaymentMethodName(id) {
  return props.paymentMethods.find(pm => pm.id === id)?.name || 'N/A'
}

function getCashAccountName(id) {
  return props.cashAccounts.find(ca => ca.id === id)?.name || 'N/A'
}

function formatCurrency(amount) {
  return Number(amount || 0).toFixed(2).replace('.', ',')
}
</script>

<style scoped>
th.w-1, td.w-1     { width: 1%; }
th.w-10, td.w-10   { width: 10%; }
th.w-25, td.w-25   { width: 25%; }

.text-right-field :deep(input) {
  text-align: right !important;
}

.no-borders :deep(table) {
  border-collapse: collapse;
}

.no-borders :deep(td),
.no-borders :deep(th) {
  border: none !important;
}

.no-borders :deep(tr) {
  border-bottom: none !important;
}
</style>
