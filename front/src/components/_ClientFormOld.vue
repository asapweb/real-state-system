<script setup>
import axios from '@/services/axios'
import { ref, watch, onMounted } from 'vue'
const emit = defineEmits(['saved', 'close'])

import { sendRequest } from '@/functions'
const props = defineProps(['client'])

const form = ref({
  name: '1',
  last_name: '2',
  type: 'person',
  company_name: '',
  business_name: '',
  document_type: 'ID Card',
  document_number: '123',
  no_document: 0,
})
const clientTypes = ['person', 'company']
const document_types = ['ID Card', 'Passport']
// Estado de errores
const errors = ref({
  document_number: '',
})

const validateUniqueDocument = async () => {
  try {
    const response = await axios.post('/api/clients/validate-document', {
      document_type: form.value.document_type,
      document_number: form.value.document_number,
      id: form.value.id, // Enviar el ID si es edición
    })

    if (!response.data.unique) {
      errors.value.document_number = 'Document type and number combination already exists.'
    } else {
      errors.value.document_number = ''
    }
  } catch (error) {
    errors.value.document_number = 'Error validating document.'
  }
}
// Validaciones del formulario
const rules = {
  required: (v) => !!v || 'This field is required',
  documentNumberRequired: (v) =>
    !form.value.no_document || !!v || 'Required if document is known',
}
// Validar automáticamente cuando cambian los valores
watch(
  () => [form.value.document_type, form.value.document_number],
  async () => {
    if (!form.value.no_document && form.value.document_type && form.value.document_number) {
      await validateUniqueDocument()
    } else {
      errors.value.document_number = ''
    }
  },
)
const nameRules = [(v) => !!v || 'This field is required']
const companyRules = [(v) => !!v || 'This field is required']
const documentRules = [
  (v) => !form.value.no_document || !!v || 'This field is required', // Validación dinámica basada en no_document
]

onMounted(() => {
  form.value.id = null
  form.value.name = null
  if (props.client) {
    form.value.id = props.client.id
    form.value.name = props.client.name
    form.value.last_name = props.client.last_name
    form.value.type = props.client.type
    form.value.company_name = props.client.company_name
    form.value.business_name = props.client.business_name
    form.value.document_type = props.client.document_type
    form.value.document_number = props.client.document_number
    form.value.no_document = Boolean(props.client.no_document)
  }
})

const toggleDocumentFields = () => {
  if (form.value.no_document) {
    form.value.document_type = null
    form.value.document_number = null
  }
}

const save = async () => {
  console.log('saveeeeee')
  let res
  if (form.value.id) {
    res = await sendRequest('PUT', form.value, '/api/clients/' + form.value.id, '')
  } else {
    res = await sendRequest('POST', form.value, '/api/clients', '')
  }
  // res.catch((errors) => {
  //   console.log('error en client')
  //   console.log(errors)
  // })

  if (res.status === 200 || res.status === 201) {
    console.log('res.data.id')
    console.log(res.data.id)
    emit('saved', res.data.id)
  }
}
</script>

<template>
  <v-form @submit.prevent="save">
    <v-card title="Cliente">
      <v-card-text>
        <!-- Tipo de cliente (persona o empresa) -->
        <v-row>
          <v-col cols="12" sm="6">
            <v-select v-model="form.type" :items="clientTypes" label="Client Type" required />
          </v-col>
        </v-row>

        <!-- Campos del cliente -->
        <v-row v-if="form.type === 'person'">
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.name"
              label="First Name"
              :rules="nameRules"
              required
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field v-model="form.last_name" label="Last Name" :rules="nameRules" required />
          </v-col>
        </v-row>

        <!-- Si el tipo de cliente es empresa, mostrar campos adicionales -->
        <v-row v-else>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.company_name"
              label="Company Name"
              :rules="companyRules"
              required
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.business_name"
              label="Business Name"
              :rules="companyRules"
              required
            />
          </v-col>
        </v-row>

        <!-- Tipo de documento y número -->
        <v-row>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.document_type"
              :items="document_types"
              label="Document Type"
              :rules="documentRules"
              :disabled="Boolean(form.no_document)"
              required
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.document_number"
              label="Document Number"
              :rules="[rules.documentNumberRequired]"
              :error-messages="errors.document_number"
              :disabled="Boolean(form.no_document)"
              required
            />
          </v-col>
        </v-row>

        <!-- Campo para marcar si no se conoce el tipo de documento -->
        <v-row>
          <v-col cols="12">
            <v-checkbox
              v-model="form.no_document"
              label="Unknown document type and number"
              @change="toggleDocumentFields"
            />
          </v-col>
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
