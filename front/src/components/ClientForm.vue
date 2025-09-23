<template>dasdasdas
  <v-form @submit.prevent="submitForm">
    <v-card :title="isEditing ? 'Editar Cliente' : 'Crear Cliente'">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.client_type"
              :items="clientTypeOptions"
              item-value="value"
              item-title="text"
              label="Tipo de Cliente"
              required
            ></v-select>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12" sm="6" v-if="form.client_type === 'person'">
            <v-text-field v-model="form.name" label="Nombre"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6" v-if="form.client_type === 'person'">
            <v-text-field v-model="form.last_name" label="Apellido"></v-text-field>
          </v-col>

          <v-col cols="12" sm="6" v-if="form.client_type === 'company'">
            <v-text-field v-model="form.company_name" label="Nombre de la Empresa"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6" v-if="form.client_type === 'company'">
            <v-text-field v-model="form.business_name" label="Razón Social"></v-text-field>
          </v-col>

          <v-col cols="12" sm="4">
            <v-select
              v-model="form.document_type_id"
              :items="documentTypes"
              item-value="id"
              item-title="name"
              label="Tipo de Documento"
              :disabled="form.no_document"
            ></v-select>
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="form.document_number"
              label="Número de Documento"
              :disabled="form.no_document"
            ></v-text-field>
          </v-col>

          <v-col cols="12" sm="3">
            <v-checkbox v-model="form.no_document" label="Desconocido"></v-checkbox>
          </v-col>
        </v-row>
        <v-row v-if="form.client_type === 'person'">
          <v-col cols="12" sm="6">
            <v-date-input
              v-model="form.birth_date"
              label="Fecha de nacimiento"
              prepend-icon=""
              locale="es"
              hide-details
            ></v-date-input>
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.gender"
              :items="clientGenderOptions"
              item-title="text"
              item-value="value"
              label="Género"
            ></v-select>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field v-model="form.cellphone" label="Celular"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field v-model="form.email" label="Email" type="email"></v-text-field>
          </v-col>

          <input type="hidden" name="client_type" :value="form.client_type" />
        </v-row>
      </v-card-text>
      <v-divider></v-divider>
      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancelar" variant="plain" @click="$emit('close')"></v-btn>

        <v-btn color="primary" text="Save" variant="tonal" type="submit">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import { sendRequest } from '@/functions'
import { VDateInput } from 'vuetify/labs/VDateInput'
import moment from 'moment'
const props = defineProps({
  clientId: {
    type: Number,
    default: null,
  },
})
const emit = defineEmits(['saved', 'close'])

const isEditing = computed(() => props.clientId !== null)

const documentTypes = ref([])

const clientTypeOptions = [
  { value: 'person', text: 'Persona' },
  { value: 'company', text: 'Empresa' },
]
const clientGenderOptions = [
  { value: 'male', text: 'Hombre' },
  { value: 'female', text: 'Mujer' },
  { value: 'other', text: 'Otro' },
]

const form = reactive({
  client_type: 'person', // Valor predeterminado
  name: '',
  last_name: '',
  company_name: '',
  business_name: '',
  document_type_id: null,
  document_number: '',
  no_document: false,
  birth_date: null,
  gender: null,
  cellphone: '',
  email: '',
  user_id: null,
  created_by: null,
  updated_by: null,
})

onMounted(async () => {
  try {
    const response = await axios.get('/api/document_types')
    documentTypes.value = response.data

    if (isEditing.value) {
      const clientResponse = await axios.get(`/api/clients/${props.clientId}`)
      const clientData = clientResponse.data.data
      console.log(clientData.birth_date)
      clientData.birth_date = clientData.birth_date ? moment(clientData.birth_date).toDate() : null
      console.log(clientData.birth_date)
      console.log(new Date('2022-02-22'))

      // clientData.birth_date = null

      Object.assign(form, clientData)
      // Object.assign(form, clientResponse.data)
    }
  } catch (error) {
    console.error('Error al cargar datos:', error)
  }
})

const dataToSubmit = computed(() => {
  const data = { ...form }

  if (data.no_document) {
    delete data.document_type_id
    delete data.document_number
  }

  if (data.client_type === 'person') {
    delete data.company_name
    delete data.business_name
  } else if (data.client_type === 'company') {
    delete data.name
    delete data.last_name
  }

  // if (data.birth_date) {
  //   data.birth_date = moment(data.birth_date).format('YYYY-MM-DD') // Format as YYYY-MM-DD
  // }
  if (data.birth_date) {
    data.birth_date = moment(data.birth_date, 'YYYY-MM-DD').format('YYYY-MM-DD') // Asegurar formato
  } else {
    data.birth_date = null
  }

  return data
})

const submitForm = async () => {
  try {
    let res
    if (isEditing.value) {
      // await axios.put(`/api/clients/${route.params.id}`, dataToSubmit.value)
      res = await sendRequest('PUT', dataToSubmit.value, `/api/clients/${props.clientId}`, '')
    } else {
      // await axios.post('/api/clients', dataToSubmit.value)
      res = await sendRequest('POST', dataToSubmit.value, '/api/clients', '')
    }
    if (res.status === 200 || res.status === 201) {
      emit('saved', res.data.id)
    }
  } catch (error) {
    console.error('Error al guardar cliente:', error)
  }
}
</script>
