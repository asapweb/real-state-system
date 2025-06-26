<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="goBack"
    >
      <svg
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M15 18L9 12L15 6"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </v-btn>
    <h2 class="">
      Editar Cliente
      <span class="font-weight-light">/ {{ clientData?.name }} {{ clientData?.last_name }}</span>
    </h2>
  </div>

  <div v-if="loadingClient" class="text-center">
    <v-progress-circular indeterminate color="primary"></v-progress-circular>
  </div>

  <ClientForm
    v-else
    :initial-data="clientData"
    @submit="updateClient"
    @cancel="goBack"
    :loading="isSubmitting"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ClientForm from './components/ClientForm.vue'
import axios from 'axios'
// import { useSnackbar } from '@/composables/useSnackbar';

const route = useRoute()
const router = useRouter()
// const snackbar = useSnackbar();

const clientData = ref(null)
const loadingClient = ref(true)
const isSubmitting = ref(false)

// Obtenemos el ID del cliente de la URL
const clientId = route.params.id

onMounted(async () => {
  try {
    const response = await axios.get(`/api/clients/${clientId}`)
    clientData.value = response.data
  } catch (error) {
    console.error('Error al cargar los datos del cliente:', error)
    // snackbar.error('No se pudo cargar el cliente');
    router.push('/clients')
  } finally {
    loadingClient.value = false
  }
})

const updateClient = async (formData) => {
  isSubmitting.value = true
  try {
    await axios.put(`/api/clients/${clientId}`, formData)
    // snackbar.success('Cliente actualizado correctamente');
    router.push('/clients')
  } catch (error) {
    console.error('Error al actualizar el cliente:', error)
    // snackbar.error('Hubo un error al actualizar el cliente');
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => {
  router.push('/clients')
}
</script>
