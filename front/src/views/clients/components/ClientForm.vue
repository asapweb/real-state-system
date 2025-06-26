<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Tipo -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.type"
                :items="clientTypes"
                item-title="title"
                item-value="value"
                label="Tipo de Cliente"
                :error-messages="v$.type.$errors.map((e) => e.$message)"
                @blur="v$.type.$touch"
              ></v-select>
            </v-col>
          </v-row>

          <!-- Nombre y Apellido o Empresa -->
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                :label="formData.type === 'company' ? 'Nombre de Empresa' : 'Nombre'"
                :error-messages="v$.name.$errors.map((e) => e.$message)"
                @blur="v$.name.$touch"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.last_name"
                label="Apellido"
                :disabled="formData.type === 'company'"
                :error-messages="v$.last_name.$errors.map((e) => e.$message)"
                @blur="v$.last_name.$touch"
              ></v-text-field>
            </v-col>
          </v-row>

          <!-- Documento -->
          <v-divider class="my-4"></v-divider>

          <v-row align="center">
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.document_type_id"
                :items="documentTypes"
                item-title="name"
                item-value="id"
                label="Tipo de Documento"
                :disabled="formData.no_document"
                :error-messages="v$.document_type_id.$errors.map((e) => e.$message)"
                @blur="v$.document_type_id.$touch"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.document_number"
                label="Número de Documento"
                :disabled="formData.no_document"
                :error-messages="v$.document_number.$errors.map((e) => e.$message)"
                @blur="v$.document_number.$touch"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-switch
                v-model="formData.no_document"
                label="No posee documento"
                color="primary"
              ></v-switch>
            </v-col>
          </v-row>

          <!-- CUIT / Condición Fiscal -->
          <v-row>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.tax_document_type_id"
                :items="documentTypes"
                item-title="name"
                item-value="id"
                label="Tipo Doc. Fiscal"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.tax_document_number"
                label="CUIT / CUIL"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.tax_condition_id"
                :items="taxConditions"
                item-title="name"
                item-value="id"
                label="Condición Fiscal"
              ></v-select>
            </v-col>
          </v-row>

          <!-- Contacto -->
          <v-divider class="my-4"></v-divider>

          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                label="Correo Electrónico"
                type="email"
                :error-messages="v$.email.$errors.map((e) => e.$message)"
                @blur="v$.email.$touch"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.phone" label="Teléfono"></v-text-field>
            </v-col>
          </v-row>

          <!-- Datos Personales -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.nationality_id"
                :items="countries"
                item-title="name"
                item-value="id"
                label="Nacionalidad"
              ></v-select>
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.civil_status_id"
                :items="civilStatuses"
                item-title="name"
                item-value="id"
                label="Estado Civil"
              ></v-select>
            </v-col>
          </v-row>

          <!-- Dirección -->
          <v-row>
            <v-col cols="12">
              <v-text-field v-model="formData.address" label="Dirección"></v-text-field>
            </v-col>
          </v-row>

          <!-- Notas -->
          <v-row>
            <v-col cols="12">
              <v-textarea v-model="formData.notes" label="Notas Adicionales" rows="3"></v-textarea>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue-grey" variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar Cliente
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, email, requiredIf } from '@vuelidate/validators'
import axios from 'axios'

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  type: 'individual',
  name: '',
  last_name: '',
  no_document: false,
  document_type_id: null,
  document_number: '',
  tax_document_type_id: null,
  tax_document_number: '',
  tax_condition_id: null,
  email: '',
  phone: '',
  address: '',
  nationality_id: null,
  civil_status_id: null,
  notes: '',
})

const clientTypes = [
  { title: 'Persona Física', value: 'individual' },
  { title: 'Empresa', value: 'company' },
]

const documentTypes = ref([])
const taxConditions = ref([])
const countries = ref([])
const civilStatuses = ref([])

const rules = {
  type: { required },
  name: { required },
  last_name: {
    required: requiredIf(() => formData.type === 'individual'),
  },
  email: {
    required: false,
    email,
  },
  document_type_id: {
    required: requiredIf(() => !formData.no_document),
  },
  document_number: {
    required: requiredIf(() => !formData.no_document),
  },
}

const v$ = useVuelidate(rules, formData)

watch(
  () => props.initialData,
  (newData) => {
    if (newData && newData.id) {
      Object.assign(formData, {
        ...newData,
        type: (newData.type || '').toLowerCase(),
      })
    }
  },
  { immediate: true, deep: true },
)

watch(
  () => formData.no_document,
  (isTrue) => {
    if (isTrue) {
      formData.document_type_id = null
      formData.document_number = ''
      v$.value.document_type_id.$reset()
      v$.value.document_number.$reset()
    }
  },
)

const handleSubmit = async () => {
  const isFormCorrect = await v$.value.$validate()
  if (!isFormCorrect) return
  emit('submit', formData)
}

onMounted(() => {
  fetchSelectData()
})

const fetchSelectData = async () => {
  try {
    const [docTypesRes, countriesRes, civilStatusesRes, taxConditionsRes] = await Promise.all([
      axios.get('/api/document-types'),
      axios.get('/api/nationalities'),
      axios.get('/api/civil-statuses'),
      axios.get('/api/tax-conditions'),
    ])
    documentTypes.value = docTypesRes.data
    countries.value = countriesRes.data
    civilStatuses.value = civilStatusesRes.data
    taxConditions.value = taxConditionsRes.data
  } catch (error) {
    console.error('Error cargando datos para los selects:', error)
  }
}
</script>
