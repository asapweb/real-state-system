<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.service_type"
                :items="serviceTypes"
                item-title="label"
                item-value="value"
                label="Tipo de servicio"
                :error-messages="v$.service_type.$errors.map((e) => e.$message)"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field v-model="form.account_number" label="Número de cuenta" />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field v-model="form.provider_name" label="Proveedor" />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field v-model="form.owner_name" label="Titular" />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch v-model="form.is_active" label="¿Servicio activo?" />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch v-model="form.has_debt" label="¿Tiene deuda?" />
            </v-col>

            <v-col cols="12" md="6" v-if="form.has_debt">
              <v-text-field v-model="form.debt_amount" label="Monto de deuda" type="number" />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.paid_by"
                :items="payerOptions"
                item-title="label"
                item-value="value"
                label="Pagado por"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea v-model="form.notes" label="Notas" rows="2" />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, numeric, minValue } from '@vuelidate/validators'

const props = defineProps({
  initialData: Object,
  loading: Boolean,
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  service_type: '',
  account_number: '',
  provider_name: '',
  owner_name: '',
  is_active: true,
  has_debt: false,
  debt_amount: null,
  paid_by: '',
  notes: '',
})

const rules = {
  service_type: { required },
  debt_amount: { numeric, minValue: minValue(0) },
}

const v$ = useVuelidate(rules, form)
const formRef = ref(null)

const serviceTypes = [
  { label: 'Electricidad', value: 'electricity' },
  { label: 'Agua', value: 'water' },
  { label: 'Gas', value: 'gas' },
  { label: 'Internet', value: 'internet' },
  { label: 'Teléfono', value: 'phone' },
]

const payerOptions = [
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
  { label: 'Inmobiliaria', value: 'agency' },
]

watch(
  () => props.initialData,
  (data) => {
    if (data && typeof data === 'object') {
      Object.assign(form, {
        service_type: data.service_type ?? '',
        account_number: data.account_number ?? '',
        provider_name: data.provider_name ?? '',
        owner_name: data.owner_name ?? '',
        is_active: data.is_active ?? true,
        has_debt: data.has_debt ?? false,
        debt_amount: data.debt_amount ?? null,
        paid_by: data.paid_by ?? '',
        notes: data.notes ?? '',
      })
    }
  },
  { immediate: true },
)

const handleSubmit = async () => {
  const valid = await v$.value.$validate()
  if (!valid) return
  emit('submit', { ...form })
}
</script>
