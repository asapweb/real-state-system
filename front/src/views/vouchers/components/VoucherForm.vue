<template>
  <v-form @submit.prevent="handleSubmit" :disabled="loading">
    <VoucherFormHeader
      :form="form"
      v-model="selectedBooklet"
      :clients="clients"
      :loading-clients="loadingClients"
      :default-type="props.voucher?.voucher_type_short_name || props.defaultType || null"
      @contract-selected="onContractSelected"
      @client-changed="onClientChanged"
    />
    <!-- Comprobantes asociados -->
    <div v-if="['N/D', 'N/C'].includes(form.voucher_type_short_name)">
      <v-divider class="mt-15 mb-5"></v-divider>
      <VoucherAssociationForm
        v-model="associatedVoucherIds"
        :type="form.voucher_type_short_name"
        :client-id="form.client_id"
        :letter="form.letter"
      />
    </div>

    <!-- Aplicaciones -->
    <div v-if="showApplicationsSection">
      <v-divider class="mt-15 mb-5"></v-divider>
        <VoucherApplicationForm
          v-if="form.client_id"
          v-model="form.applications"
          :client-id="form.client_id"
          :currency="form.currency"
          :voucher-type-short-name="form.voucher_type_short_name"
        />
    </div>

    <!-- Conceptos -->
    <div class="mt-15">
      <v-divider class="mt-15 mb-5"></v-divider>
      <VoucherFormItems
        :form="form"
        :taxRates="taxRates"
        @items-updated="debouncedCalculateTotals"
        @creation-row-changed="onItemsCreationRowChanged"
      />
    </div>

    <!-- Pagos -->
    <div v-if="showPaymentsSection">
      <v-divider class="mt-15 mb-5"></v-divider>
      <VoucherFormPayments
        :form="form"
        :paymentMethods="paymentMethods"
        :cashAccounts="cashAccounts"
        :loadingPaymentMethods="loadingPaymentMethods"
        :loadingCashAccounts="loadingCashAccounts"
        @creation-row-changed="onPaymentsCreationRowChanged"
        @items-updated="debouncedCalculateTotals"
      />
    </div>

    <!-- Notas y totales -->
    <div>
      <v-divider class="mt-15 mb-5"></v-divider>
      <VoucherFormNotesTotals :form="form" :totals="totals"/>
    </div>

    <v-btn
      color="primary"
      type="submit"
      :loading="loading"
      :disabled="!canSave"
      :title="!canSave ? 'Complete o cancele la creación de elementos antes de guardar' : ''"
    >
      Guardar
    </v-btn>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import axios from '@/services/axios'
import voucherService from '@/services/voucherService'
import { useSnackbar } from '@/composables/useSnackbar'
import { debounce } from 'lodash'

import VoucherFormHeader from './VoucherFormHeader.vue'
import VoucherFormItems from './VoucherFormItems.vue'
import VoucherFormNotesTotals from './VoucherFormNotesTotals.vue'
import VoucherFormPayments from './VoucherFormPayments.vue'
import VoucherApplicationForm from './VoucherApplicationForm.vue'
import VoucherAssociationForm from './VoucherAssociationForm.vue'

const emit = defineEmits(['saved'])
const snackbar = useSnackbar()

const props = defineProps({
  voucher: { type: Object, default: null },
  defaultType: { type: String, default: null }
})

const loading = ref(false)
const loadingClients = ref(false)
const clients = ref([])
const selectedBooklet = ref(null)

// Estados de creación de items y pagos
const isCreatingItem = ref(false)
const isCreatingPayment = ref(false)

const loadingPaymentMethods = ref(false)
const loadingCashAccounts = ref(false)
const paymentMethods = ref([])
const cashAccounts = ref([])

const form = reactive({
  booklet_id: null,
  voucher_type_id: null,
  currency: 'ARS',
  issue_date: new Date().toISOString().split('T')[0],
  due_date: new Date().toISOString().split('T')[0],
  service_date_from: new Date().toISOString().split('T')[0],
  service_date_to: new Date().toISOString().split('T')[0],
  client_id: null,
  client_name: '',
  client_address: '',
  client_document_number: '',
  client_iva_condition_id: 16,
  client_document_type_id: null,
  contract_id: null,
  period: '',
  notes: '',
  total: 0,
  voucher_type: null,
  letter: null,
  voucher_type_short_name: '',
  items: [],
  payments: [],
  applications: [],
})

const associatedVoucherIds = ref([])

const taxRates = ref([])

const showPaymentsSection = computed(() => {
  return form?.voucher_type_short_name === 'RCB' || form?.voucher_type_short_name === 'RPG'
})
const showApplicationsSection = computed(() => {
  return form?.voucher_type_short_name === 'RCB' || form?.voucher_type_short_name === 'RPG'
  // return ['RCB', 'RCP', 'N/C'].includes(form.voucher_type_short_name">
})

const canSave = computed(() => {
  return !isCreatingItem.value && !isCreatingPayment.value
})

const totals = reactive({
  subtotal_untaxed: 0,
  subtotal_exempt: 0,
  subtotal_taxed: 0,
  subtotal_vat: 0,
  subtotal_other_taxes: 0,
  total: 0,
})

const debouncedCalculateTotals = debounce(async () => {
  // if (form.items.length === 0) {
  //   Object.assign(totals, {
  //     subtotal_untaxed: 0,
  //     subtotal_exempt: 0,
  //     subtotal_taxed: 0,
  //     subtotal_vat: 0,
  //     subtotal_other_taxes: 0,
  //     total: 0,
  //   })
  //   return
  // }

  try {
    const response = await axios.post('/api/vouchers/preview-totals', {
      voucher_type_short_name: form.voucher_type_short_name,
      voucher_type_letter: form.letter,
      currency: form.currency,
      items: form.items,
      applications: form.applications,
      payments: form.payments,
    })
    Object.assign(totals, response.data)
  } catch (error) {
    console.error('Error calculating totals:', error)
    snackbar.error('Error al calcular los totales.')
  }
}, 500)

watch(() => form.items, debouncedCalculateTotals, { deep: true })
watch(() => form.applications, () => {
  debouncedCalculateTotals()
}, { deep: true })

watch(selectedBooklet, (newBooklet) => {
  if (newBooklet && newBooklet.voucher_type) {
    form.booklet_id = newBooklet.id
    form.currency = newBooklet.default_currency || 'ARS'
    form.voucher_type_short_name = newBooklet.voucher_type.short_name || ''
    form.voucher_type_id = newBooklet.voucher_type.id || null
    form.letter = newBooklet.voucher_type.letter || null
    form.voucher_type_letter = newBooklet.voucher_type.letter || null
    form.items.forEach(item => {
      if (!['A', 'B'].includes(form.letter)) {
        item.tax_rate_id = null
      }
    })
    // form.items = []
  } else {
    form.booklet_id = null
    form.voucher_type_short_name = ''
    form.voucher_type_id = null
  }
})

watch(() => form.client_id, (newClientId) => {
  if (newClientId) {
    const selectedClient = clients.value.find((c) => c.id === newClientId)
    if (selectedClient) {
      form.client_name = selectedClient.full_name
      form.client_address = selectedClient.address
      form.client_document_number = selectedClient.document_number
      form.client_document_type_id = selectedClient.document_type_id
      form.client_iva_condition_id = selectedClient.tax_condition_id || 16
    }
  } else {
    form.client_name = ''
    form.client_address = ''
    form.client_document_number = ''
    form.client_document_type_id = null
    form.client_iva_condition_id = 16
  }
}, { immediate: true })

onMounted(() => {
  fetchClients()
  fetchTaxRates()
  fetchPaymentMethods()
  fetchCashAccounts()
  if (props.voucher) initializeFormWithVoucher()
})

function onContractSelected(contractId) {
  if (!contractId) return
  axios.get(`/api/contracts/${contractId}`).then(({ data }) => {
    const contract = data.data
    if (!form.client_id && contract.main_tenant_id) {
      form.client_id = contract.main_tenant_id
    }
    if (contract.payment_day && form.period) {
      const [year, month] = form.period.split('-')
      const lastDay = new Date(year, month, 0).getDate()
      const day = Math.min(contract.payment_day, lastDay)
      form.due_date = `${year}-${month}-${day.toString().padStart(2, '0')}`
    }
  })
}

function onClientChanged(newClientId) {
  // Limpiar el contrato seleccionado cuando cambie el cliente
  form.contract_id = null
}

function onItemsCreationRowChanged(isCreating) {
  isCreatingItem.value = isCreating
}

function onPaymentsCreationRowChanged(isCreating) {
  isCreatingPayment.value = isCreating
}

async function fetchClients() {
  loadingClients.value = true
  try {
    const { data } = await axios.get('/api/clients', { params: { per_page: 50 } })
    clients.value = data.data || data
  } finally {
    loadingClients.value = false
  }
}

async function fetchTaxRates() {
  try {
    const response = await axios.get('/api/tax-rates')
    taxRates.value = response.data
  } catch (error) {
    console.error('Error fetching tax rates:', error)
  }
}

async function fetchPaymentMethods() {
  loadingPaymentMethods.value = true
  try {
    const response = await axios.get('/api/payment-methods/active')
    paymentMethods.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching payment methods:', error)
  } finally {
    loadingPaymentMethods.value = false
  }
}

async function fetchCashAccounts() {
  loadingCashAccounts.value = true
  try {
    const response = await axios.get('/api/cash-accounts/active')
    cashAccounts.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching cash accounts:', error)
  } finally {
    loadingCashAccounts.value = false
  }
}

async function handleSubmit() {
  loading.value = true
  try {
    // Validar que no haya filas de creación activas
    if (isCreatingItem.value || isCreatingPayment.value) {
      snackbar.error('No se puede guardar el voucher mientras hay elementos en proceso de creación. Complete o cancele la creación primero.')
      loading.value = false
      return
    }

    const formData = { ...form }

    if (formData.items?.length > 0) {
      formData.items = formData.items.map(item => ({
        type:item.type,
        description: item.description,
        quantity: Number(item.quantity) || 1,
        unit_price: Number(item.unit_price) || 0,
        tax_rate_id: item.tax_rate_id,
      }))
    }

    if ((formData.voucher_type_short_name === 'RCB' || formData.voucher_type_short_name === 'RPG') && formData.payments?.length > 0) {
      formData.payments = formData.payments.map(payment => ({
        payment_method_id: payment.payment_method_id,
        cash_account_id: payment.cash_account_id,
        amount: Number(payment.amount) || 0,
        reference: payment.reference,
      }))
    }

    formData.associated_voucher_ids = associatedVoucherIds.value

    let response
    if (props.voucher) {
      response = await voucherService.update(props.voucher.id, formData)
      snackbar.success('Voucher actualizado correctamente')
    } else {
      response = await voucherService.create(formData)
      snackbar.success('Voucher creado correctamente')
    }
    emit('saved', response.data.data)
  } catch (error) {
    console.error('Error saving voucher:', error)
    if (error.response?.status === 422 && error.response.data.errors) {
      for (const field in error.response.data.errors) {
        // snackbar.error(`${field}: ${error.response.data.errors[field][0]}`)
      }
      snackbar.error(` ${error.response.data.message}`)
    } else {
      snackbar.error(error.response?.data?.message || error.message || 'Error desconocido')
    }
  } finally {
    loading.value = false
  }
}

function initializeFormWithVoucher() {
  const voucher = props.voucher

  // Primero asignar los datos básicos sin contract_id
  Object.assign(form, {
    booklet_id: voucher.booklet_id,
    voucher_type_id: voucher.voucher_type_id,
    currency: voucher.currency,
    issue_date: voucher.issue_date,
    due_date: voucher.due_date,
    afip_operation_type_id: voucher.afip_operation_type_id,
    service_date_from: voucher.service_date_from,
    service_date_to: voucher.service_date_to,
    client_id: voucher.client_id,
    client_name: voucher.client_name,
    client_address: voucher.client_address,
    client_document_number: voucher.client_document_number,
    client_iva_condition_id: voucher.client_iva_condition_id,
    client_document_type_id: voucher.client_document_type_id,
    period: voucher.period,
    notes: voucher.notes,
    voucher_type_short_name: voucher.voucher_type_short_name,
  })

  // Asignar contract_id después de un pequeño delay para que el ContractAutocomplete se inicialice correctamente
  if (voucher.contract_id) {
    console.log('Inicializando contrato:', voucher.contract_id)
    setTimeout(() => {
      form.contract_id = voucher.contract_id
      console.log('Contrato asignado:', form.contract_id)
    }, 100)
  }

  if (voucher.items?.length) {
    form.items = voucher.items.map(item => ({
      type: item.type || 'service',
      description: item.description,
      quantity: item.quantity,
      unit_price: item.unit_price,
      tax_rate_id: item.tax_rate_id,
    }))
  }

  if (voucher.payments?.length) {
    form.payments = voucher.payments.map(payment => ({
      payment_method_id: payment.payment_method_id,
      cash_account_id: payment.cash_account_id,
      amount: payment.amount,
      reference: payment.reference,
    }))
  }

  if (voucher.applications?.length) {
    form.applications = voucher.applications.map(app => ({
      applied_to_id: app.applied_to_id,
      amount: app.amount,
      voucher: {
        id: app.applied_to_id,
        full_number: app.applied_voucher_full_number,
        date: app.applied_voucher_issue_date,
        total: app.applied_voucher_total,
        applied: app.applied_voucher_applied,
        pending: app.applied_voucher_pending,
      }
    }))
  }

  if (voucher.associations?.length) {
    associatedVoucherIds.value = voucher.associations.map(a => a.associated_voucher_id)
  }

  if (voucher.booklet) {
    selectedBooklet.value = voucher.booklet
  }
}
</script>
