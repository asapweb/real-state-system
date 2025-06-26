<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <v-row>
            <!-- Postulante -->
            <v-col cols="12" md="6">
              <ClientAutocomplete
                v-model="formData.applicant_id"
                :error-messages="v$.applicant_id.$errors.map(e => e.$message)"
              />
            </v-col>

            <!-- Oferta de alquiler -->
            <v-col cols="12" md="6">
              <RentalOfferAutocomplete
                v-model="formData.rental_offer_id"
                :error-messages="[]"
                @selected="handleRentalOfferSelected"
                :disabled="!!props.initialData?.rental_offer_id"
              />

            </v-col>

            <!-- Propiedad (solo si no hay oferta seleccionada) -->
            <v-col cols="12" md="6" v-if="!formData.rental_offer_id">
              <PropertyAutocomplete
                v-model="formData.property_id"
                :error-messages="v$.property_id.$errors.map(e => e.$message)"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Seguro -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.insurance_responsible"
                :items="insuranceOptions"
                item-title="label"
                item-value="value"
                label="Responsable del seguro"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.insurance_required_from"
                label="Desde cuándo se requiere seguro"
                type="date"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Seña -->
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.reservation_amount"
                label="Monto de reserva"
                type="number"
                min="0"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.currency"
                :items="currencyOptions"
                item-title="label"
                item-value="value"
                label="Moneda"
                :error-messages="v$.currency.$errors.map(e => e.$message)"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Estado y notas -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Estado"
                :error-messages="v$.status.$errors.map(e => e.$message)"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model="formData.notes"
                label="Notas"
                rows="3"
                auto-grow
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" color="blue-grey" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar Solicitud
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, requiredIf } from '@vuelidate/validators'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'
import PropertyAutocomplete from '@/views/components/PropertyAutocomplete.vue'
import RentalOfferAutocomplete from '@/views/components/RentalOfferAutocomplete.vue'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  property_id: null,
  rental_offer_id: null,
  applicant_id: null,
  insurance_responsible: null,
  insurance_required_from: null,
  reservation_amount: null,
  currency: 'ARS',
  status: 'draft',
  notes: ''
})

const rules = {
  applicant_id: { required },
  rental_offer_id: {},
  property_id: { required: requiredIf(() => !formData.rental_offer_id) },
  status: { required },
  currency: { required }
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'En evaluación', value: 'under_review' },
  { label: 'Aprobado', value: 'approved' },
  { label: 'Rechazado', value: 'rejected' }
]

const currencyOptions = [
  { label: 'Pesos Argentinos (ARS)', value: 'ARS' },
  { label: 'Dólares (USD)', value: 'USD' }
]

const insuranceOptions = [
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
  { label: 'Inmobiliaria', value: 'agency' }
]

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...formData })
}

const handleRentalOfferSelected = (offer) => {
  if (offer?.property_id) {
    formData.property_id = offer.property_id
  }
}

onMounted(() => {
  if (props.initialData?.id) {
    Object.assign(formData, props.initialData)
  } else {
    // Carga inicial en caso de nueva solicitud con oferta predefinida
    if (props.initialData?.rental_offer_id) {
      formData.rental_offer_id = props.initialData.rental_offer_id
      handleRentalOfferSelected({ property_id: props.initialData.property_id })
    }
  }
})

</script>
