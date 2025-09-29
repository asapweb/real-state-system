<template>
  <v-card>
    <v-form ref="formRef" @submit.prevent="submitForm">
      <v-card-text class="pt-6">
        <v-row>
          <v-col cols="12" md="6">
            <ContractAutocomplete
              v-if="!isEditing"
              v-model="form.contract_id"
              :error-messages="v$?.contract_id?.$errors?.map(getMessage) || []"
            />
            <v-text-field
              v-else
              label="Contrato"
              :model-value="contractSummary"
              variant="solo-filled"
              density="compact"
              readonly
              hide-details
            />
          </v-col>
          <v-col cols="12" md="6">
            <ChargeTypeSelect
              v-if="!isEditing"
              v-model="form.charge_type_id"
              :error-messages="v$?.charge_type_id?.$errors?.map(getMessage) || []"
              @loaded="cacheChargeTypes"
            />
            <v-text-field
              v-else
              label="Tipo de cargo"
              :model-value="chargeTypeLabel"
              variant="solo-filled"
              density="compact"
              readonly
              hide-details
            />
          </v-col>
        </v-row>

        <v-alert
          v-if="isCanceled"
          type="warning"
          variant="tonal"
          density="comfortable"
          class="mb-4"
        >
          <div class="font-weight-medium mb-1">
            {{ cancellationHeadline }}
          </div>
          <div v-if="meta.canceled_reason" class="text-body-2">
            Motivo: {{ meta.canceled_reason }}
          </div>
        </v-alert>
        <v-alert
          v-else-if="isFinancialLocked"
          type="info"
          variant="tonal"
          density="comfortable"
          class="mb-4"
        >
          Este cargo está asociado a una liquidación asentada. Solo podés editar la descripción.
        </v-alert>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.amount"
              label="Monto"
              type="number"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.amount?.$errors?.map(getMessage) || []"
              min="0"
              step="0.01"
              :disabled="isFinancialLocked"
            />
          </v-col>
          <v-col cols="12" md="4">
            <CurrencySelect
              v-model="form.currency"
              :error-messages="v$?.currency?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
          <v-col cols="12" md="4">
            <ServiceTypeSelect
              v-if="showServiceTypeField"
              v-model="form.service_type_id"
              :error-messages="v$?.service_type_id?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.effective_date"
              label="Fecha efectiva"
              type="date"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.effective_date?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.due_date"
              label="Vencimiento"
              type="date"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.due_date?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.invoice_date"
              label="Fecha factura"
              type="date"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.invoice_date?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
        </v-row>

        <v-row v-if="showServicePeriodFields">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.service_period_start"
              label="Inicio periodo"
              type="date"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.service_period_start?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.service_period_end"
              label="Fin periodo"
              type="date"
              variant="solo-filled"
              density="compact"
              :error-messages="v$?.service_period_end?.$errors?.map(getMessage) || []"
              :disabled="isFinancialLocked"
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
              density="compact"
              :loading="counterpartyLoading"
              :disabled="isFinancialLocked || counterpartyOptions.length === 0"
              :error-messages="v$?.counterparty_contract_client_id?.$errors?.map(getMessage) || []"
              :hint="counterpartyHint"
              persistent-hint
            />
          </v-col>
          <v-col v-if="!counterpartyLoading && counterpartyOptions.length === 0" cols="12">
            <v-alert type="warning" variant="tonal" density="comfortable">
              Agregá al contrato una persona con el rol requerido para poder continuar.
            </v-alert>
          </v-col>
        </v-row>

        <v-textarea
          v-model="form.description"
          rows="3"
          label="Descripción"
          variant="solo-filled"
          auto-grow
        />

        <v-row v-if="isEditing && hasMetaInfo" class="mt-4">
          <v-col cols="12" md="6" v-if="meta.tenant_liquidation_voucher_id">
            <v-alert type="info" variant="tonal" density="comfortable">
              Asociado a LIQ Inquilino
              <RouterLink
                :to="{ name: 'VoucherShow', params: { id: meta.tenant_liquidation_voucher_id } }"
                class="text-primary text-decoration-none ms-1"
              >
                {{ formatModelId(meta.tenant_liquidation_voucher_id, 'VOU') }}
              </RouterLink>
            </v-alert>
          </v-col>
          <v-col cols="12" md="6" v-if="meta.owner_liquidation_voucher_id">
            <v-alert type="info" variant="tonal" density="comfortable">
              Asociado a LIQ Propietario
              <RouterLink
                :to="{ name: 'VoucherShow', params: { id: meta.owner_liquidation_voucher_id } }"
                class="text-primary text-decoration-none ms-1"
              >
                {{ formatModelId(meta.owner_liquidation_voucher_id, 'VOU') }}
              </RouterLink>
            </v-alert>
          </v-col>
          <v-col cols="12" md="6" v-if="meta.tenant_settled_at">
            <v-alert type="success" variant="outlined" density="comfortable">
              Asentado lado inquilino el {{ formatDateTime(meta.tenant_settled_at) }}
            </v-alert>
          </v-col>
          <v-col cols="12" md="6" v-if="meta.owner_settled_at">
            <v-alert type="success" variant="outlined" density="comfortable">
              Asentado lado propietario el {{ formatDateTime(meta.owner_settled_at) }}
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" :loading="props.loading" type="submit">Guardar</v-btn>
      </v-card-actions>
    </v-form>
  </v-card>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, requiredIf } from '@vuelidate/validators'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ChargeTypeSelect from '@/views/components/form/ChargeTypeSelect.vue'
import ServiceTypeSelect from '@/views/components/form/ServiceTypeSelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import { formatDateTime } from '@/utils/date-formatter'
import { formatModelId } from '@/utils/models-formatter'

const props = defineProps({
  initialData: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const snackbar = useSnackbar()
const formRef = ref(null)

const form = reactive({
  id: null,
  contract_id: null,
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

const meta = reactive({
  tenant_liquidation_voucher_id: null,
  owner_liquidation_voucher_id: null,
  tenant_settled_at: null,
  owner_settled_at: null,
  is_canceled: false,
  canceled_at: null,
  canceled_reason: null,
  canceled_by: null,
  canceled_by_user: null,
})

const chargeTypesCache = ref([])
const contractClients = ref([])
const counterpartyLoading = ref(false)
const formContractDetails = ref(null)

const isEditing = computed(() => Boolean(form.id))

const selectedChargeType = computed(() => {
  if (!form.charge_type_id) {
    return props.initialData?.charge_type ?? null
  }
  return (
    chargeTypesCache.value.find(type => Number(type.id) === Number(form.charge_type_id))
    || props.initialData?.charge_type
    || null
  )
})

const showServiceTypeField = computed(() => Boolean(selectedChargeType.value?.requires_service_type))

const chargeTypeLabel = computed(() => {
  const ct = selectedChargeType.value
  if (!ct) return '—'
  return ct.name || ct.code || `#${ct.id}`
})

const showServicePeriodFields = computed(() => {
  const ct = selectedChargeType.value
  if (!ct) return false
  if (typeof ct.requires_service_period !== 'undefined') return Boolean(ct.requires_service_period)
  return Boolean(ct.requires_service_type)
})

const requiresCounterparty = computed(() => Boolean(selectedChargeType.value?.requires_counterparty))

const counterpartyRoleLabels = {
  tenant: 'Inquilino',
  owner: 'Propietario',
}

const counterpartyHint = computed(() => {
  const role = (selectedChargeType.value?.requires_counterparty || '').toString().toLowerCase()
  if (!role) return ''
  return `Seleccioná la persona marcada como ${counterpartyRoleLabels[role] || role} dentro del contrato.`
})

const counterpartyOptions = computed(() => {
  const desiredRole = (selectedChargeType.value?.requires_counterparty || '').toString().toLowerCase()
  return contractClients.value
    .filter(item => {
      if (!desiredRole) return true
      return (item.role || '').toLowerCase() === desiredRole
    })
    .map(item => {
      const client = item.client || {}
      const label = [client.last_name, client.name].filter(Boolean).join(', ') || client.company_name || `Cliente #${item.client_id}`
      const roleLabel = counterpartyRoleLabels[(item.role || '').toLowerCase()] || ''
      return {
        value: item.id,
        title: roleLabel ? `${label} (${roleLabel})` : label,
      }
    })
})

const hasMetaInfo = computed(() => Boolean(
  meta.tenant_liquidation_voucher_id
  || meta.owner_liquidation_voucher_id
  || meta.tenant_settled_at
  || meta.owner_settled_at
))

const isCanceled = computed(() => Boolean(meta.is_canceled))
const hasSettledLock = computed(() => Boolean(meta.tenant_settled_at || meta.owner_settled_at))
const isFinancialLocked = computed(() => isEditing.value && (isCanceled.value || hasSettledLock.value))

const canceledByName = computed(() => {
  const user = meta.canceled_by_user
  if (!user) return ''
  return user.name || user.email || `Usuario #${user.id}`
})

const canceledAtLabel = computed(() => (meta.canceled_at ? formatDateTime(meta.canceled_at) : ''))

const cancellationHeadline = computed(() => {
  if (!isCanceled.value) return ''
  let base = 'Cancelado'
  if (canceledAtLabel.value) {
    base += ` el ${canceledAtLabel.value}`
  }
  if (canceledByName.value) {
    base += ` por ${canceledByName.value}`
  }
  return base
})

const contractSummary = computed(() => {
  if (!formContractDetails.value) return form.contract_id ? `Contrato #${form.contract_id}` : '—'
  const code = formContractDetails.value.code || `CON-${String(formContractDetails.value.id ?? '').padStart(6, '0')}`
  const tenant = formContractDetails.value.tenant_name || formContractDetails.value.client?.full_name || ''
  return tenant ? `${code} · ${tenant}` : code
})

const notZero = helpers.withMessage('El monto debe ser mayor a 0.', (value) => {
  const parsed = Number(value)
  return !Number.isNaN(parsed) && parsed > 0
})

const rules = computed(() => ({
  contract_id: { required },
  charge_type_id: { required },
  amount: { required, notZero },
  currency: { required },
  effective_date: { required },
  due_date: {},
  invoice_date: {},
  service_type_id: { required: requiredIf(() => showServiceTypeField.value) },
  service_period_start: { required: requiredIf(() => showServicePeriodFields.value) },
  service_period_end: { required: requiredIf(() => showServicePeriodFields.value) },
  counterparty_contract_client_id: { required: requiredIf(() => requiresCounterparty.value) },
  description: {},
}))

const v$ = useVuelidate(rules, form)

const getMessage = (error) => error.$message || 'Campo requerido'

const cacheChargeTypes = (types) => {
  chargeTypesCache.value = types || []
  applyChargeTypeDefaults()
}

const applyChargeTypeDefaults = () => {
  const ct = selectedChargeType.value
  if (!ct) return
  if (!ct.requires_service_type) {
    form.service_type_id = null
  }
  if (!showServicePeriodFields.value) {
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
    const { data } = await axios.get(`/api/contracts/${contractId}/clients`, { params: { per_page: 100 } })
    const list = Array.isArray(data) ? data : data?.data
    contractClients.value = Array.isArray(list) ? list : []
  } catch (error) {
    console.error(error)
    snackbar?.error('No pudimos cargar las personas del contrato')
    contractClients.value = []
  } finally {
    counterpartyLoading.value = false
  }
}

const ensureCounterpartyIsValid = () => {
  if (!counterpartyOptions.value.some(opt => opt.value === form.counterparty_contract_client_id)) {
    form.counterparty_contract_client_id = null
  }
}

const loadContractDetails = async (contractId) => {
  if (!contractId || isEditing.value) return
  try {
    const { data } = await axios.get('/api/contracts/lookup', { params: { id: contractId, limit: 1 } })
    formContractDetails.value = data?.data?.[0] || null
  } catch (error) {
    console.error(error)
  }
}

watch(() => form.contract_id, (contractId) => {
  fetchContractClients(contractId)
  if (!isEditing.value) {
    form.counterparty_contract_client_id = null
    loadContractDetails(contractId)
  }
})

watch(counterpartyOptions, ensureCounterpartyIsValid)

watch(() => form.charge_type_id, applyChargeTypeDefaults)

watch(
  () => props.initialData,
  (data) => {
    if (!data) return
    Object.assign(form, {
      id: data.id || null,
      contract_id: data.contract_id || data.contract?.id || null,
      charge_type_id: data.charge_type_id || data.charge_type?.id || null,
      service_type_id: data.service_type_id || data.service_type?.id || null,
      counterparty_contract_client_id: data.counterparty_contract_client_id || data.counterparty?.id || null,
      amount: data.amount != null ? String(Number(data.amount).toFixed(2)) : '',
      currency: data.currency || 'ARS',
      effective_date: data.effective_date || '',
      due_date: data.due_date || '',
      service_period_start: data.service_period_start || '',
      service_period_end: data.service_period_end || '',
      invoice_date: data.invoice_date || '',
      description: data.description || '',
    })

    Object.assign(meta, {
      tenant_liquidation_voucher_id: data.tenant_liquidation_voucher_id || null,
      owner_liquidation_voucher_id: data.owner_liquidation_voucher_id || null,
      tenant_settled_at: data.tenant_settled_at || null,
      owner_settled_at: data.owner_settled_at || null,
      is_canceled: Boolean(data.is_canceled),
      canceled_at: data.canceled_at || null,
      canceled_reason: data.canceled_reason || null,
      canceled_by: data.canceled_by || null,
      canceled_by_user: data.canceled_by_user || null,
    })

    formContractDetails.value = data.contract || null
    fetchContractClients(form.contract_id)
  },
  { immediate: true }
)

const buildPayload = () => {
  const base = {
    contract_id: form.contract_id,
    charge_type_id: form.charge_type_id,
    service_type_id: form.service_type_id || null,
    counterparty_contract_client_id: form.counterparty_contract_client_id || null,
    amount: form.amount ? Number(form.amount) : null,
    currency: form.currency ? form.currency.toUpperCase() : null,
    effective_date: form.effective_date || null,
    due_date: form.due_date || null,
    service_period_start: form.service_period_start || null,
    service_period_end: form.service_period_end || null,
    invoice_date: form.invoice_date || null,
    description: form.description || null,
  }

  return base
}

const submitForm = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', buildPayload())
}
</script>
