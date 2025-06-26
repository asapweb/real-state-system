<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Clientes</h2>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="router.push('/clients/create')"
      >
        Crear Cliente
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1" >
      Personas físicas o jurídicas. <br> Pueden ser propietarios, inquilinos, garantes, proveedores o combinaciones.

    </p>
  </div>
  <v-row>
    <v-col cols="12" class="mb-4">
          <ClientTable
            ref="clientTableRef"
            @edit-client="handleEdit"
            @delete-client="handleDelete"
            @error="handleError"
          />
    </v-col>
  </v-row>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import ClientTable from './components/ClientTable.vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const clientTableRef = ref(null)
const router = useRouter()
const snackbar = useSnackbar()

const handleEdit = (client) => {
  router.push(`/clients/${client.id}/edit`)
}

const handleDelete = async (client) => {
  try {
    await axios.delete(`/api/clients/${client.id}`)
    snackbar.success('Cliente eliminado correctamente')
    clientTableRef.value?.reload()
  } catch (error) {
    console.error(error)
    snackbar.error('No se pudo eliminar el cliente')
  }
}

const handleError = (message) => {
  snackbar.error(message)
}
</script>
