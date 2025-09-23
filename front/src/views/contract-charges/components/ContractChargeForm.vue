<template>
  <v-card>
    <v-card-text>
      <v-form @submit.prevent="submitForm">
        <v-row>
          <v-col cols="12">
            <ContractAutocomplete
              v-if="!props.contractId"
              v-model="form.contract_id"
              :error-messages="v$?.contract_id?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
        </v-row>

        <v-row v-if="requiresCounterparty">
          <v-col cols="12" md="6">
            <v-select
              v-model="form.counterparty_contract_client_id"
              :items="counterpartyOptions"
              item-title="title"
              item-value="value"
              label="Contraparte"
              variant="solo-filled"
              :loading="counterpartyLoading"
              :disabled="!form.contract_id || counterpartyOptions.length === 0"
              :error-messages="v$?.counterparty_contract_client_id?.$errors?.map(e => e.$message) || []"
              :hint="counterpartyHint"
              :persistent-hint="Boolean(counterpartyHint)"
            />
          </v-col>
          <v-col v-if="requiresCounterparty && form.contract_id && !counterpartyLoading && counterpartyOptions.length === 0" cols="12">
            <v-alert type="warning" variant="tonal" density="comfortable">
              No encontramos personas con el rol requerido en este contrato. Agregue la persona desde el contrato antes de continuar.
            </v-alert>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="6">
            <ChargeTypeSelect
              v-model="form.charge_type_id"
              :error-messages="v$?.charge_type_id?.$errors?.map(e => e.$message) || []"
              @loaded="cacheChargeTypes"
            />
          </v-col>
          <v-col v-if="showServiceTypeField" cols="12" md="6">
            <ServiceTypeSelect
              v-model="form.service_type_id"
              :error-messages="v$?.service_type_id?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.amount"
              label="Monto"
              type="number"
              variant="solo-filled"
              :error-messages="v$?.amount?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
          <v-col cols="12" md="6">
            <CurrencySelect v-model="form.currency" />
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.effective_date"
              label="Fecha efectiva"
              type="date"
              variant="solo-filled"
              :error-messages="v$?.effective_date?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.due_date"
              label="Vencimiento"
              type="date"
              variant="solo-filled"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.invoice_date"
              label="Fecha factura"
              type="date"
              variant="solo-filled"
            />
          </v-col>
        </v-row>

        <v-row v-if="requiresServicePeriod">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.service_period_start"
              label="Inicio período servicio"
              type="date"
              variant="solo-filled"
              :error-messages="v$?.service_period_start?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.service_period_end"
              label="Fin período servicio"
              type="date"
              variant="solo-filled"
              :error-messages="v$?.service_period_end?.$errors?.map(e => e.$message) || []"
            />
          </v-col>
        </v-row>

        <v-textarea
          v-model="form.description"
          label="Descripción (opcional)"
          variant="solo-filled"
          rows="2"
        />
      </v-form>
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn text @click="$emit('cancel')">Cancelar</v-btn>
      <v-btn color="primary" :loading="loading" @click="submitForm">Guardar</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, requiredIf, helpers } from '@vuelidate/validators'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ChargeTypeSelect from '@/views/components/form/ChargeTypeSelect.vue'
import ServiceTypeSelect from '@/views/components/form/ServiceTypeSelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'

const props = defineProps({
  initialData: { type: Object, default: null },
  contractId: { type: Number, required: false, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const snackbar = useSnackbar()

let chargeTypesCache = []

const contractClients = ref([])
const counterpartyLoading = ref(false)

const form = reactive({
  id: null,
  contract_id: props.contractId || null,
  charge_type_id: null,
  service_type_id: null,
  counterparty_contract_client_id: null,
  amount: '',
  currency: 'ARS',
  effective_date: '',
  due_date: '',
  service_period_start: '',
  service_period_end: '',
  invoice_date: '',
  description: '',
})

const counterpartyRoleLabels = {
  tenant: 'Inquilino',
  owner: 'Propietario',
  guarantor: 'Garante',
  supplier: 'Proveedor',
}

const selectedChargeType = computed(() => {
  const id = form.charge_type_id
  if (id == null) return null
  return chargeTypesCache.find(t => Number(t.id) === Number(id)) || null
})

const showServiceTypeField = computed(() => !!selectedChargeType.value?.requires_service_type)

const requiresServicePeriod = computed(() => {
  const ct = selectedChargeType.value
  if (!ct) return false
  if (typeof ct.requires_service_period !== 'undefined') {
    return Boolean(ct.requires_service_period)
  }
  return Boolean(ct.requires_service_type)
})

const counterpartyRole = computed(() => {
  const value = selectedChargeType.value?.requires_counterparty
  if (value === null || typeof value === 'undefined' || value === false) {
    return null
  }
  return String(value).toLowerCase()
})

const requiresCounterparty = computed(() => !!counterpartyRole.value)

const counterpartyHint = computed(() => {
  if (!requiresCounterparty.value) return ''
  const roleLabel = counterpartyRoleLabels[counterpartyRole.value] || 'contraparte'
  return `Seleccioná el/la ${roleLabel} asociado al contrato.`
})

const counterpartyOptions = computed(() => {
  const desiredRole = counterpartyRole.value
  return contractClients.value
    .filter(item => {
      if (!desiredRole) return true
      const role = (item.role || '').toString().toLowerCase()
      return role === desiredRole
    })
    .map(item => {
      const client = item.client || {}
      const nameParts = [client.last_name, client.name].filter(Boolean)
      const displayName = nameParts.length ? nameParts.join(', ') : (client.company_name || `Cliente #${item.client_id}`)
      const roleLabel = counterpartyRoleLabels[(item.role || '').toString().toLowerCase()] || ''
      const title = roleLabel ? `${displayName} (${roleLabel})` : displayName
      return { value: item.id, title }
    })
})

const notZero = helpers.withMessage('El importe no puede ser 0.', (v) => {
  const n = Number(v)
  return !Number.isNaN(n) && n !== 0
})

const rules = computed(() => ({
  contract_id: { required: !props.contractId },
  charge_type_id: { required },
  service_type_id: { required: requiredIf(() => showServiceTypeField.value) },
  counterparty_contract_client_id: { required: requiredIf(() => requiresCounterparty.value) },
  amount: { required, notZero },
  currency: { required },
  effective_date: { required },
  service_period_start: { required: requiredIf(() => requiresServicePeriod.value) },
  service_period_end: { required: requiredIf(() => requiresServicePeriod.value) },
}))

const v$ = useVuelidate(rules, form)

const cacheChargeTypes = (types) => {
  chargeTypesCache = types || []
  applyChargeTypeEffects(selectedChargeType.value)
}

const applyChargeTypeEffects = (ct) => {
  if (!ct) {
    form.service_type_id = null
    form.service_period_start = ''
    form.service_period_end = ''
    form.invoice_date = ''
    form.counterparty_contract_client_id = null
    return
  }

  if (!ct.requires_service_type) {
    form.service_type_id = null
  }

  const needsPeriod = typeof ct.requires_service_period !== 'undefined'
    ? Boolean(ct.requires_service_period)
    : Boolean(ct.requires_service_type)
  if (!needsPeriod) {
    form.service_period_start = ''
    form.service_period_end = ''
  }

  if (!ct.requires_counterparty) {
    form.counterparty_contract_client_id = null
  }
}

const fetchContractClients = async (contractId) => {
  if (!contractId) {
    contractClients.value = []
    return
  }

  counterpartyLoading.value = true
  try {
    const { data } = await axios.get(`/api/contracts/${contractId}/clients`, {
      params: { per_page: 100 },
    })
    const list = Array.isArray(data) ? data : (data?.data ?? [])
    contractClients.value = list
  } catch (error) {
    console.error('Error cargando personas del contrato:', error)
    snackbar?.error('No se pudieron cargar las personas del contrato')
    contractClients.value = []
  } finally {
    counterpartyLoading.value = false
  }
}

watch(
  () => form.charge_type_id,
  () => {
    applyChargeTypeEffects(selectedChargeType.value)
  }
)

watch(
  () => form.contract_id,
  (contractId) => {
    fetchContractClients(contractId)
    if (!contractId) {
      form.counterparty_contract_client_id = null
    }
  },
  { immediate: true }
)

watch(counterpartyOptions, (options) => {
  if (!options.some(opt => opt.value === form.counterparty_contract_client_id)) {
    form.counterparty_contract_client_id = null
  }
})

watch(
  () => props.initialData,
  (data) => {
    if (data) {
      Object.assign(form, {
        id: data.id || null,
        contract_id: data.contract_id || props.contractId || null,
        charge_type_id: data.charge_type_id || null,
        service_type_id: data.service_type?.id || data.service_type_id || null,
        counterparty_contract_client_id: data.counterparty?.id || data.counterparty_contract_client_id || null,
        amount: data.amount ?? '',
        currency: data.currency || 'ARS',
        effective_date: data.effective_date || '',
        due_date: data.due_date || '',
        service_period_start: data.service_period_start || '',
        service_period_end: data.service_period_end || '',
        invoice_date: data.invoice_date || '',
        description: data.description || '',
      })
      if (form.contract_id) {
        fetchContractClients(form.contract_id)
      }
    }
  },
  { immediate: true }
)

const submitForm = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return

  const payload = {
    contract_id: form.contract_id,
    charge_type_id: form.charge_type_id,
    service_type_id: form.service_type_id || null,
    counterparty_contract_client_id: form.counterparty_contract_client_id || null,
    amount: form.amount,
    currency: form.currency ? form.currency.toUpperCase() : undefined,
    effective_date: form.effective_date,
    due_date: form.due_date || null,
    service_period_start: form.service_period_start || null,
    service_period_end: form.service_period_end || null,
    invoice_date: form.invoice_date || null,
    description: form.description || null,
  }

  emit('submit', payload)
}
</script>
