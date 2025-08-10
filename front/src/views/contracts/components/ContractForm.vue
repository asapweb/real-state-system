<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Solicitud y Propiedad -->
          <v-row>
            <v-col cols="12" md="6">
              <RentalApplicationAutocomplete
                v-model="formData.rental_application_id"
                @selected="handleRentalApplicationSelected"
              />
            </v-col>
            <v-col cols="12" md="6">
              <template v-if="formData.rental_application_id && selectedProperty">
                <v-text-field :model-value="selectedProperty" label="Propiedad" readonly />
              </template>
              <template v-else>
                <PropertyAutocomplete
                  v-model="formData.property_id"
                  :error-messages="v$.property_id.$errors.map((e) => e.$message)"
                />
              </template>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Vigencia y monto -->
          <v-row>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="formData.start_date"
                label="Desde"
                type="date"
                :error-messages="v$.start_date.$errors.map((e) => e.$message)"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="formData.end_date"
                label="Hasta"
                type="date"
                :error-messages="v$.end_date.$errors.map((e) => e.$message)"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="formData.currency"
                :items="currencyOptions"
                label="Moneda"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.monthly_amount"
                label="Monto mensual"
                type="number"
                :error-messages="v$.monthly_amount.$errors.map((e) => e.$message)"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field v-model="formData.payment_day" label="Día de pago" type="number" />
            </v-col>
          </v-row>
          <!-- Punitorios -->
          <v-row>
            <v-col cols="12" md="3">
              <v-switch v-model="formData.has_penalty" label="¿Tiene punitorio?" />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="formData.penalty_type"
                :items="penaltyTypeOptions"
                label="Tipo"
                item-title="label"
                item-value="value"
                :disabled="!formData.has_penalty"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.penalty_value"
                label="Valor"
                type="number"
                :disabled="!formData.has_penalty"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.penalty_grace_days"
                label="Días de gracia"
                type="number"
                :disabled="!formData.has_penalty"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Comisión -->
          <v-row>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.commission_type"
                :items="commissionTypeOptions"
                label="Tipo comisión"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.commission_amount"
                label="Monto comisión"
                type="number"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.commission_payer"
                :items="commissionPayerOptions"
                label="Lo paga"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12" md="4" v-if="formData.commission_payer === 'tenant'">
              <v-checkbox v-model="formData.is_one_time" label="Cobrar comisión solo una vez" />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Prorrateo -->
          <v-row>
            <v-col cols="12" md="6">
              <v-checkbox
                v-model="formData.prorate_first_month"
                label="Prorratear primer mes (si no comienza el día 1)"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-checkbox
                v-model="formData.prorate_last_month"
                label="Prorratear último mes (si finaliza antes del último día)"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Talonarios -->
          <v-row>
            <v-col cols="12" md="6">
              <BookletSelect
                v-model="selectedCollectionBooklet"
                voucher-type="FAC"
                letter="X"
                label="Talonario para cobranzas"
              />
            </v-col>
            <v-col cols="12" md="6">
              <BookletSelect
                v-model="selectedSettlementBooklet"
                voucher-type="LIQ"
                label="Talonario para liquidaciones"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Seguro -->
          <v-row>
            <v-col cols="12" md="4">
              <v-switch v-model="formData.insurance_required" label="¿Requiere seguro?" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.insurance_amount"
                label="Monto seguro"
                type="number"
                :disabled="!formData.insurance_required"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.insurance_company_name"
                label="Compañía"
                :disabled="!formData.insurance_required"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Depósito -->
          <v-row>
            <v-col cols="12" md="3">
              <v-text-field v-model="formData.deposit_amount" label="Depósito" type="number" />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="formData.deposit_currency"
                :items="currencyOptions"
                label="Moneda"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.deposit_type"
                :items="depositTypeOptions"
                label="Tipo depósito"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="formData.deposit_holder"
                :items="depositHolderOptions"
                label="Tenencia"
                item-title="label"
                item-value="value"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          

          <v-divider class="my-4" />

          <!-- Estado y notas -->
          <v-row>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.status"
                :items="statusOptions"
                label="Estado"
                item-title="label"
                item-value="value"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formData.notes" label="Notas internas" rows="2" auto-grow />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid"
          >Guardar</v-btn
        >
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { reactive, ref, onMounted, watchEffect } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, numeric } from '@vuelidate/validators'
import RentalApplicationAutocomplete from '@/views/components/RentalApplicationAutocomplete.vue'
import PropertyAutocomplete from '@/views/components/PropertyAutocomplete.vue'
import BookletSelect from '@/views/components/form/BookletSelect.vue'

const props = defineProps({
  initialData: Object,
  loading: Boolean,
})
const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  rental_application_id: null,
  property_id: null,
  start_date: null,
  end_date: null,
  monthly_amount: null,
  currency: 'ARS',
  payment_day: null,
  commission_type: 'none',
  commission_amount: null,
  commission_payer: null,
  is_one_time: false,
  prorate_first_month: false,
  prorate_last_month: false,
  insurance_required: true,
  insurance_amount: null,
  insurance_company_name: '',
  owner_share_percentage: 100,
  deposit_amount: null,
  deposit_currency: 'ARS',
  deposit_type: 'none',
  deposit_holder: null,
  has_penalty: false,
  penalty_type: 'none',
  penalty_value: null,
  penalty_grace_days: 0,
  status: 'draft',
  notes: '',
  collection_booklet_id: null,
  settlement_booklet_id: null,
})

const selectedCollectionBooklet = ref(null)
const selectedSettlementBooklet = ref(null)

watchEffect(() => {
  formData.collection_booklet_id = selectedCollectionBooklet.value?.id ?? null
  formData.settlement_booklet_id = selectedSettlementBooklet.value?.id ?? null
})

const rules = {
  property_id: { required },
  start_date: { required },
  end_date: { required },
  monthly_amount: { required, numeric },
  currency: { required },
  status: { required },
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)
const selectedProperty = ref(null)

const handleRentalApplicationSelected = (application) => {
  if (application?.property_id && application?.property?.street) {
    formData.property_id = application.property_id
    selectedProperty.value =
      `${application.property?.street} ${application.property?.number || ''}`.trim()
  }
}

const currencyOptions = [
  { label: 'Pesos (ARS)', value: 'ARS' },
  { label: 'Dólares (USD)', value: 'USD' },
  { label: 'Euros (EUR)', value: 'EUR' },
]

const commissionTypeOptions = [
  { label: 'Ninguna', value: 'none' },
  { label: 'Fija', value: 'fixed' },
  { label: 'Porcentaje', value: 'percentage' },
]

const commissionPayerOptions = [
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
]

const depositTypeOptions = [
  { label: 'Ninguno', value: 'none' },
  { label: 'Fijo', value: 'fixed' },
  { label: 'Meses', value: 'months' },
]

const depositHolderOptions = [
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
  { label: 'Inmobiliaria', value: 'agency' },
]

const penaltyTypeOptions = [
  { label: 'Ninguno', value: 'none' },
  { label: 'Fijo', value: 'fixed' },
  { label: 'Porcentaje', value: 'percentage' },
]

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'Activo', value: 'active' },
  { label: 'Finalizado', value: 'completed' },
  { label: 'Rescindido', value: 'terminated' },
]

onMounted(() => {
  if (props.initialData?.id) {
    Object.assign(formData, {
      ...props.initialData,
      start_date: props.initialData.start_date?.substring(0, 10) || null,
      end_date: props.initialData.end_date?.substring(0, 10) || null,
    })
  }

  // Después de que formData esté poblado
  if (props.initialData?.collection_booklet) {
    selectedCollectionBooklet.value = props.initialData.collection_booklet
  }

  if (props.initialData?.settlement_booklet) {
    selectedSettlementBooklet.value = props.initialData.settlement_booklet
  }
})

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...formData })
}
</script>
