<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Tipo de Propiedad -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.property_type_id"
                :items="propertyTypes"
                item-title="name"
                item-value="id"
                label="Tipo de Propiedad"
                :error-messages="v$.property_type_id.$errors.map((e) => e.$message)"
                @blur="v$.property_type_id.$touch"
                required
              />
            </v-col>
          </v-row>

          <!-- Dirección -->
          <v-divider class="my-4" />
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.street"
                label="Calle"
                :error-messages="v$.street.$errors.map((e) => e.$message)"
                @blur="v$.street.$touch"
              />
            </v-col>
            <v-col cols="6" md="3">
              <v-text-field v-model="formData.number" label="Número" />
            </v-col>
            <v-col cols="6" md="3">
              <v-text-field v-model="formData.floor" label="Piso" />
            </v-col>
            <v-col cols="6" md="3">
              <v-text-field v-model="formData.apartment" label="Depto." />
            </v-col>
            <v-col cols="6" md="3">
              <v-text-field v-model="formData.postal_code" label="CP" />
            </v-col>
          </v-row>

          <!-- Ubicación -->
          <v-divider class="my-4" />
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.country_id"
                :items="countries"
                item-title="name"
                item-value="id"
                label="País"
                :error-messages="v$.country_id.$errors.map((e) => e.$message)"
                @blur="v$.country_id.$touch"
                @update:model-value="loadStates"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.state_id"
                :items="states"
                item-title="name"
                item-value="id"
                label="Provincia"
                :error-messages="v$.state_id.$errors.map((e) => e.$message)"
                @blur="v$.state_id.$touch"
                @update:model-value="loadCities"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.city_id"
                :items="cities"
                item-title="name"
                item-value="id"
                label="Ciudad"
                :error-messages="v$.city_id.$errors.map((e) => e.$message)"
                @blur="v$.city_id.$touch"
                @update:model-value="loadNeighborhoods"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.neighborhood_id"
                :items="neighborhoods"
                item-title="name"
                item-value="id"
                label="Barrio"
                :error-messages="v$.neighborhood_id.$errors.map((e) => e.$message)"
                @blur="v$.neighborhood_id.$touch"
              />
            </v-col>
          </v-row>

          <!-- Datos técnicos -->
          <v-divider class="my-4" />
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field v-model="formData.tax_code" label="Partida" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="formData.cadastral_reference" label="Nomenclatura Catastral" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="formData.registry_number" label="Matrícula" />
            </v-col>
            <v-col cols="12" md="6">
              <v-switch v-model="formData.has_parking" label="Tiene cochera" />
            </v-col>
            <v-col cols="12" md="6" v-if="formData.has_parking">
              <v-text-field v-model="formData.parking_details" label="Detalles de cochera" />
            </v-col>
            <v-col cols="12" md="6">
              <v-switch v-model="formData.allows_pets" label="Admite mascotas" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="formData.iva_condition" label="Condición IVA" />
            </v-col>
          </v-row>

          <!-- Estado y observaciones -->
          <v-divider class="my-4" />
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Estado"
                :error-messages="v$.status.$errors.map((e) => e.$message)"
                @blur="v$.status.$touch"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model="formData.observations"
                label="Observaciones"
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
          Guardar Propiedad
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required } from '@vuelidate/validators'
import axios from '@/services/axios'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  property_type_id: null,
  street: '',
  number: '',
  floor: '',
  apartment: '',
  postal_code: '',
  country_id: null,
  state_id: null,
  city_id: null,
  neighborhood_id: null,
  tax_code: '',
  cadastral_reference: '',
  registry_number: '',
  has_parking: false,
  parking_details: '',
  allows_pets: false,
  iva_condition: '',
  status: 'draft',
  observations: '',
})

const rules = {
  property_type_id: { required },
  street: { required },
  country_id: { required },
  state_id: { required },
  city_id: { required },
  neighborhood_id: { required },
  status: { required },
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

const propertyTypes = ref([])
const countries = ref([])
const states = ref([])
const cities = ref([])
const neighborhoods = ref([])

const statusOptions = [
  { value: 'draft', label: 'Borrador' },
  { value: 'published', label: 'Publicado' },
  { value: 'archived', label: 'Archivado' },
]

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...formData })
}

onMounted(() => {
  loadSelects()
  if (props.initialData?.id) {
    Object.assign(formData, props.initialData)
  }
})

const loadSelects = async () => {
  const [types, countriesRes] = await Promise.all([
    axios.get('/api/property-types'),
    axios.get('/api/countries'),
  ])
  propertyTypes.value = types.data
  countries.value = countriesRes.data

  if (!props.initialData?.id) {
    const defType = propertyTypes.value.find((i) => i.is_default)
    if (defType) formData.property_type_id = defType.id
    const defCountry = countries.value.find((i) => i.is_default)
    if (defCountry) formData.country_id = defCountry.id
  }

  await loadStates()
}

const loadStates = async () => {
  if (!formData.country_id) return
  const { data } = await axios.get('/api/states', { params: { country_id: formData.country_id } })
  states.value = data
  if (!props.initialData?.id) {
    const def = data.find((i) => i.is_default)
    if (def) formData.state_id = def.id
  }
  await loadCities()
}

const loadCities = async () => {
  if (!formData.state_id) return
  const { data } = await axios.get('/api/cities', { params: { state_id: formData.state_id } })
  cities.value = data
  if (!props.initialData?.id) {
    const def = data.find((i) => i.is_default)
    if (def) formData.city_id = def.id
  }
  await loadNeighborhoods()
}

const loadNeighborhoods = async () => {
  if (!formData.city_id) return
  const { data } = await axios.get('/api/neighborhoods', { params: { city_id: formData.city_id } })
  neighborhoods.value = data
  if (!props.initialData?.id) {
    const def = data.find((i) => i.is_default)
    if (def) formData.neighborhood_id = def.id
  }
}
</script>
