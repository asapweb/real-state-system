<script setup>
import axios from '@/services/axios'
import { ref, computed, reactive, onMounted } from 'vue'
import { sendRequest } from '@/functions'

// import { getDepartments, createUser } from '@/services/user'; // Importa los servicios
const props = defineProps({
  client: {
    type: Object,
    dedault: null,
  },
})

const emit = defineEmits(['saved', 'close'])

const isEditing = computed(() => props.client !== null)
const form = reactive(props.client || { name: '', email: '', password: '', departments: [] }) // Inicializa user

const loading = ref(false)
const getDepartments = async (query) => {
  loading.value = true
  await axios
    .get('/api/departments', {
      params: {
        search: { name: query },
      },
    })
    .then((response) => {
      departments.value = response.data.data
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}
const departments = ref([])

onMounted(async () => {
  await getDepartments()
  if (props.client) {
    form.name = props.client.name
    form.email = props.client.email
    //form.password = props.client.password; // No deberías enviar la contraseña en la edición
    // form.departments = props.client.departments;
    form.departments = props.client.departments.map((dept) => dept.id || dept)
  }
  // departments.value = ['1111', '222222'] //await getDepartments();
})

const submitForm = async () => {
  try {
    let res
    if (isEditing.value) {
      res = await sendRequest('PUT', form, '/api/users/' + form.id, '')
    } else {
      res = await sendRequest('POST', form, '/api/users', '')
    }
    if (res.status === 200 || res.status === 201) {
      console.log('res.data.id')
      console.log(res.data.id)
      emit('saved', res.data.id)
    }
    // await createUser(form);
    // Redirigir o mostrar un mensaje de éxito
  } catch (error) {
    // Mostrar mensajes de error
  }
}
</script>
<template>
  <v-card title="Usuario">
    <v-card-text>
      <v-form @submit.prevent="submitForm">
        <v-text-field v-model="form.name" label="Nombre"></v-text-field>
        <v-text-field v-model="form.email" label="Email"></v-text-field>
        <v-text-field
          v-if="!isEditing"
          v-model="form.password"
          label="Contraseña"
          type="password"
        ></v-text-field>
        <v-select
          v-model="form.departments"
          label="Departamentos"
          :items="departments"
          item-value="id"
          item-title="name"
          multiple
          :rules="[(v) => !!v || 'Debes seleccionar al menos un departamento']"
        ></v-select>
        <v-btn type="submit">Guardar</v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>
