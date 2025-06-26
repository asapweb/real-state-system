<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <v-row>
            <!-- Cliente -->
            <v-col cols="12" md="6">
              <ClientAutocomplete
                v-model="formData.client_id"
                :error-messages="v$.client_id.$errors.map(e => e.$message)"
              />
            </v-col>

            <!-- Rol -->
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.role"
                :items="roleOptions"
                item-title="label"
                item-value="value"
                label="Rol en la solicitud"
                :error-messages="v$.role.$errors.map(e => e.$message)"
                @blur="v$.role.$touch"
              />
            </v-col>

            <!-- Vínculo -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.relationship"
                label="Vínculo con el postulante"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Datos laborales -->
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.occupation" label="Ocupación" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.employer" label="Empresa" />
            </v-col>
            <v-col cols="6" md="3">
              <v-text-field
                v-model="formData.income"
                label="Ingresos mensuales"
                type="number"
                min="0"
              />
            </v-col>
            <v-col cols="6" md="3">
              <v-select
                v-model="formData.currency"
                :items="currencyOptions"
                item-title="label"
                item-value="value"
                label="Moneda"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.seniority" label="Antigüedad laboral" />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Datos conyugales y propiedad -->
          <v-row>
            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_property_owner"
                label="¿Es propietario de inmueble?"
              />
            </v-col>
            <v-col cols="12" md="6" v-if="formData.is_property_owner">
              <v-text-field
                v-model="formData.owned_property_address"
                label="Domicilio del inmueble"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.marital_status" label="Estado civil" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.spouse_name" label="Nombre del cónyuge" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.nationality" label="Nacionalidad" />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" color="blue-grey" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required } from '@vuelidate/validators'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  client_id: null,
  role: null,
  relationship: '',
  occupation: '',
  employer: '',
  income: null,
  currency: 'ARS',
  seniority: '',
  is_property_owner: false,
  owned_property_address: '',
  marital_status: '',
  spouse_name: '',
  nationality: ''
})

const rules = {
  client_id: { required },
  role: { required },
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

const roleOptions = [
  { value: 'applicant', label: 'Postulante' },
  { value: 'guarantor', label: 'Garante' },
  { value: 'co-applicant', label: 'Co-solicitante' },
  { value: 'spouse', label: 'Cónyuge' }
]

const currencyOptions = [
  { value: 'ARS', label: 'Pesos Argentinos (ARS)' },
  { value: 'USD', label: 'Dólares (USD)' }
]

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...formData })
}

onMounted(() => {
  if (props.initialData?.id) {
    Object.assign(formData, props.initialData)
  }
})
</script>
