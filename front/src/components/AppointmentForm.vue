<script setup>
import axios from '@/services/axios'
import { ref, onMounted } from 'vue'
import ClientAutocomplete from '@/components/ClientAutocomplete.vue'
import UserAutocomplete from '@/components/UserAutocomplete.vue'
import DepartmentAutocomplete from '@/components/DepartmentAutocomplete.vue'
const emit = defineEmits(['saved', 'close'])

import { sendRequest } from '@/functions'
const props = defineProps(['appointmentId', 'status'])

const form = ref({
  notes: null,
  client_id: null,
  department_id: null,
  employee_id: null,
  status: props.status,
})

onMounted(async () => {
  if (props.appointmentId) {
    const response = await axios.get('/api/appointments/' + props.appointmentId, {})
    form.value = response.data.data
  }
})

const save = async () => {
  let res
  if (form.value.id) {
    res = await sendRequest('PUT', form.value, '/api/appointments/' + form.value.id, '')
  } else {
    res = await sendRequest('POST', form.value, '/api/appointments', '')
  }

  if (res.status === 200 || res.status === 201) {
    emit('saved')
  }
}
</script>

<template>
  FORM NEW <br />
  <v-form @submit.prevent="save">
    <v-card title="Visita">
      <v-card-text>
        <!-- Campos del cliente -->
        <v-row>
          <v-col cols="12">
            <ClientAutocomplete v-model="form.client_id" label="Cliente" />
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12">
            <DepartmentAutocomplete v-model="form.department_id" />
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12">
            <UserAutocomplete v-model="form.employee_id" label="Staff" />
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12">
            <v-textarea
              v-model="form.notes"
              label="Notes"
              row-height="40"
              rows="4"
              auto-grow
              counter
            ></v-textarea>
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
