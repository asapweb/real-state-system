<template>
  <h2 class="text-h6">
    Comprobantes Imputados
    <v-btn
      prepend-icon="mdi-plus"
      size="small"
      color="primary"
      variant="text"
      class="ml-2"
      @click="showCreationRow = true"
      v-if="!showCreationRow"
    >
      Agregar Comprobante
    </v-btn>
  </h2>

  <v-table striped="even" class="no-borders">
    <thead>
      <tr>
        <th class="font-weight-bold text-left w-30">Comprobante</th>
        <th class="font-weight-bold text-left">Emisión</th>
        <th class="font-weight-bold text-end">Total</th>
        <th class="font-weight-bold text-end">Imputado</th>
        <th class="font-weight-bold text-end">Pendiente</th>
        <th class="font-weight-bold text-end">Importe a Imputar</th>
        <th class="font-weight-bold text-center">&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="7" v-if="applications.length === 0 && !showCreationRow">
          <v-alert type="info" variant="tonal" density="compact">
            No hay comprobantes imputados para mostrar.
          </v-alert>
        </td>
      </tr>

      <!-- Aplicaciones existentes -->
      <tr v-for="(application, index) in applications" :key="index">
        <td>{{ application.voucher?.full_number || application.applied_to_id }}</td>
        <td>{{ formatDate(application.voucher?.date || '') }}</td>
        <td class="text-end">{{ formatCurrency(application.voucher?.total || 0) }}</td>
        <td class="text-end">{{ formatCurrency(application.voucher?.applied || 0) }}</td>
        <td class="text-end">{{ formatCurrency(application.voucher?.pending || 0) }}</td>
        <td>
          <v-text-field
            v-model.number="application.amount"
            type="number"
            min="0"
            :max="application.voucher?.pending || 0"
            density="compact"
            variant="underlined"
            hide-details
            class="text-right-field"
            @blur="updateApplication(index)"
          />
        </td>
        <td class="text-center w-1">
          <v-btn
            icon="mdi-delete"
            variant="text"
            size="x-small"
            color="red-darken-2"
            @click="removeApplication(index)"
          />
        </td>
      </tr>

      <!-- Fila de nueva aplicación -->
      <tr v-if="showCreationRow">
        <td>
          <VoucherAutocomplete
            v-model="newApplication.applied_to_id"
            :client-id="clientId"
            :currency="currency"
            :voucher-type-short-name="voucherTypeShortName"
            density="compact"
            variant="underlined"
            hide-details
            placeholder="Buscar comprobante"
            @update:model-value="onVoucherSelected"
          />
        </td>
        <td>{{ selectedVoucher?.issue_date || '' }}</td>
        <td class="text-end">{{ formatCurrency(selectedVoucher?.total || 0) }}</td>
        <td class="text-end">{{ formatCurrency(selectedVoucher?.applied || 0) }}</td>
        <td class="text-end">{{ formatCurrency(selectedVoucher?.pending || 0) }}</td>
        <td>
          <v-text-field
            v-model.number="newApplication.amount"
            type="number"
            min="0"
            :max="selectedVoucher?.pending || 0"
            density="compact"
            variant="underlined"
            hide-details
            class="text-right-field"
            placeholder="0.00"
          />
        </td>
        <td class="text-center" nowrap>
          <v-btn
            icon="mdi-check"
            size="x-small"
            color="primary"
            variant="text"
            :disabled="!canAddApplication"
            @click="addApplication"
          />
          <v-btn
            icon="mdi-close"
            size="x-small"
            color="grey"
            variant="text"
            @click="cancelApplication"
          />
        </td>
      </tr>
    </tbody>
  </v-table>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import axios from '@/services/axios'
import VoucherAutocomplete from '@/components/VoucherAutocomplete.vue'
import { formatDate } from '@/utils/date-formatter'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  clientId: {
    type: [Number, String],
    required: true
  },
  currency: {
    type: String,
    default: 'ARS'
  },
  voucherTypeShortName: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'applications-updated', 'creationRowChanged'])

const showCreationRow = ref(false)
const selectedVoucher = ref(null)
const applications = ref([])

// Emitir cambios en showCreationRow
watch(showCreationRow, (newValue) => {
  emit('creationRowChanged', newValue)
})

const newApplication = reactive({
  applied_to_id: null,
  amount: 0,
  description: ''
})

const canAddApplication = computed(() => {
  const hasVoucher = !!newApplication.applied_to_id
  const hasAmount = newApplication.amount > 0
  const amountValid = newApplication.amount <= (selectedVoucher.value?.pending || 0)

  return hasVoucher && hasAmount && amountValid
})

function onVoucherSelected(voucherId) {
  if (!voucherId) {
    selectedVoucher.value = null
    newApplication.amount = 0
    return
  }

  // Buscar el voucher seleccionado
  fetchVoucherDetails(voucherId)
}

async function fetchVoucherDetails(voucherId) {
  try {
    // Buscar el voucher en la lista de vouchers con pending
    const params = {
      client_id: props.clientId,
      currency: props.currency,
      per_page: 100 // Aumentar para asegurar que encontremos el voucher
    }

    if (props.voucherTypeShortName) {
      params.voucher_type_short_name = props.voucherTypeShortName
    }

    const { data } = await axios.get('/api/vouchers/with-pending-by-client', { params })

    const vouchersList = data.data || data
    const voucherWithPending = vouchersList.find(v => v.id == voucherId)

    if (voucherWithPending) {
      selectedVoucher.value = voucherWithPending
      // Establecer el monto por defecto como el pendiente
      newApplication.amount = selectedVoucher.value.pending || 0
    } else {
      console.error('Voucher not found in pending list')
    }
  } catch (error) {
    console.error('Error fetching voucher details:', error)
  }
}

function addApplication() {
  const application = {
    applied_to_id: newApplication.applied_to_id,
    amount: newApplication.amount,
    description: newApplication.description,
    voucher: selectedVoucher.value
  }

  applications.value.push(application)
  emit('update:modelValue', applications.value)
  emit('applications-updated')
  resetNewApplication()
  showCreationRow.value = false
}

function cancelApplication() {
  resetNewApplication()
  showCreationRow.value = false
}

function resetNewApplication() {
  newApplication.applied_to_id = null
  newApplication.amount = 0
  newApplication.description = ''
  selectedVoucher.value = null
}

function removeApplication(index) {
  applications.value.splice(index, 1)
  emit('update:modelValue', applications.value)
  emit('applications-updated')
}

function updateApplication(index) {
  emit('update:modelValue', applications.value)
  emit('applications-updated')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: props.currency
  }).format(value)
}

// Inicializar applications con el modelValue
watch(() => props.modelValue, (newValue) => {
  applications.value = [...newValue]
}, { immediate: true })
</script>

<style scoped>
th.w-1, td.w-1     { width: 1%; }
th.w-10, td.w-10   { width: 10%; }
th.w-15, td.w-15   { width: 15%; }
th.w-30, td.w-30   { width: 30%; }
th.w-40, td.w-40   { width: 40%; }

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
