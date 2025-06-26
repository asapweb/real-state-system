<template>
  <v-form @submit.prevent="save">
    <v-card title="Departamento">
      <v-card-text>
        <v-row>
          <v-col>
            <v-text-field label="Nombre" v-model="form.name" type="text"></v-text-field>
            <v-text-field
              hint="Es el nombre que se muestra en la sala de espera. Ej. Oficina 1"
              persistent-hint
              clearable
              label="Ubicación"
              v-model="form.location"
              type="text"
            ></v-text-field>
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

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['saved', 'close'])

import { sendRequest } from '@/functions'
const props = defineProps(['department'])

const form = ref({ id: '', name: '', location: '' })

onMounted(() => {
  form.value.id = null
  form.value.name = null
  form.value.location = null
  if (props.department) {
    form.value.id = props.department.id
    form.value.name = props.department.name
    form.value.location = props.department.location
  }
})

const save = async () => {
  let res
  if (form.value.id) {
    res = await sendRequest('PUT', form.value, '/api/departments/' + form.value.id, '')
  } else {
    res = await sendRequest('POST', form.value, '/api/departments', '')
  }

  console.log(res.status === 200)
  if (res.status === 200) {
    emit('saved')
  }
}
</script>
