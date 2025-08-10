<template>
  <v-card>
    <v-card-title class="text-h6">
      {{ form.id ? 'Editar gasto de contrato' : 'Nuevo gasto de contrato' }}
    </v-card-title>
    <v-card-text>
      <v-form @submit.prevent="submitForm">
        <div class="d-flex flex-column gap-4">
          <!-- Contrato (solo cuando no hay contractId fijo) -->
          <ContractAutocomplete
            v-if="!props.contractId"
            v-model="form.contract_id"
            :error-messages="v$.contract_id?.$errors.map(e => e.$message)"
          />

          <!-- Tipo de servicio -->
          <ServiceTypeSelect
            v-model="form.service_type_id"
            :error-messages="v$.service_type_id.$errors.map(e => e.$message)"
          />

          <!-- Monto y moneda -->
          <div class="d-flex gap-2">
            <v-text-field
              v-model="form.amount"
              label="Monto"
              type="number"
              variant="solo-filled"
              :error-messages="v$.amount.$errors.map(e => e.$message)"
              class="flex-grow-1"
            />
            <CurrencySelect
              v-model="form.currency"
              class="flex-grow-1"
            />
          </div>

          <!-- Fechas -->
          <div class="d-flex gap-2">
            <v-text-field
              v-model="form.effective_date"
              label="Fecha efectiva"
              type="date"
              variant="solo-filled"
              :error-messages="v$.effective_date.$errors.map(e => e.$message)"
              class="flex-grow-1"
            />
            <v-text-field
              v-model="form.due_date"
              label="Fecha de vencimiento"
              type="date"
              variant="solo-filled"
              class="flex-grow-1"
            />
          </div>

          <!-- Pagado por / Responsable -->
          <div class="d-flex gap-2">
            <v-select
              v-model="form.paid_by"
              :items="paidByOptions"
              label="Pagado por"
              variant="solo-filled"
              :disabled="isLockedPaidFields"
              :error-messages="v$.paid_by.$errors.map(e => e.$message)"
              class="flex-grow-1"
            />
            <v-select
              v-model="form.responsible_party"
              :items="responsiblePartyOptions"
              label="Responsable"
              variant="solo-filled"
              :disabled="isLockedPaidFields"
              :error-messages="v$.responsible_party.$errors.map(e => e.$message)"
              class="flex-grow-1"
            />
          </div>

          <!-- Descripción -->
          <v-textarea
            v-model="form.description"
            label="Descripción (opcional)"
            variant="solo-filled"
            rows="2"
          />
        </div>
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
import { reactive, watch, computed } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, minValue } from '@vuelidate/validators'
import ServiceTypeSelect from '@/views/components/form/ServiceTypeSelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'

const props = defineProps({
  initialData: { type: Object, default: null },
  contractId: { type: Number, required: false, default: null },
  loading: { type: Boolean, default: false }
})

const isLockedPaidFields = computed(() => {
  return props.initialData?.status?.value === 'validated' ||
         ['billed', 'credited', 'liquidated'].includes(props.initialData?.status?.value)
})

const emit = defineEmits(['submit', 'cancel'])

// Formulario reactivo
const form = reactive({
  id: null,
  contract_id: props.contractId || null,
  service_type_id: null,
  amount: '',
  currency: 'ARS',
  effective_date: '',
  due_date: '',
  paid_by: '',
  responsible_party: '',
  description: ''
})

// Opciones de selects
const paidByOptions = [
  { title: 'Inquilino', value: 'tenant' },
  { title: 'Propietario', value: 'owner' },
  { title: 'Inmobiliaria', value: 'agency' }
]

const responsiblePartyOptions = [
  { title: 'Inquilino', value: 'tenant' },
  { title: 'Propietario', value: 'owner' }
]

// Validaciones dinámicas
const rules = {
  contract_id: { required: !props.contractId }, // Solo requerido si no viene de prop
  service_type_id: { required },
  amount: { required, minValue: minValue(0.01) },
  currency: { required },
  effective_date: { required },
  paid_by: { required },
  responsible_party: { required }
}

const v$ = useVuelidate(rules, form)

// Cargar datos iniciales si es edición
watch(
  () => props.initialData,
  (data) => {
    if (data) {
      Object.assign(form, {
        id: data.id || null,
        contract_id: data.contract_id || props.contractId || null,
        service_type_id: data.service_type?.id || null,
        amount: data.amount || '',
        currency: data.currency || 'ARS',
        effective_date: data.effective_date || '',
        due_date: data.due_date || '',
        paid_by: data.paid_by || '',
        responsible_party: data.responsible_party || '',
        description: data.description || ''
      })
    } else {
      Object.assign(form, {
        id: null,
        contract_id: props.contractId || null,
        service_type_id: null,
        amount: '',
        currency: 'ARS',
        effective_date: '',
        due_date: '',
        paid_by: '',
        responsible_party: '',
        description: ''
      })
    }
  },
  { immediate: true }
)

// Submit
const submitForm = async () => {
  const valid = await v$.value.$validate()
  if (!valid) return
  
  const payload = { ...form }

  // Si está en estado validated, eliminamos campos bloqueados
  if (props.initialData?.status?.value === 'validated') {
    delete payload.paid_by
    delete payload.responsible_party
  }

  emit('submit', payload)
}
</script>
