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
      Editar Propiedad
      <span class="font-weight-light">/ {{ formatModelId(property?.id, 'PRO') }}</span>
    </h2>
  </div>

  <PropertyForm
    v-if="property"
    :initial-data="property"
    :loading="isSubmitting"
    @submit="updateProperty"
    @cancel="goBack"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import PropertyForm from './components/PropertyForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatModelId } from '@/utils/models-formatter'
const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()

const property = ref(null)
const isSubmitting = ref(false)
const propertyId = route.params.id

const goBack = () => {
  router.push('/properties')
}

const fetchProperty = async () => {
  try {
    const { data } = await axios.get(`/api/properties/${propertyId}`)
    property.value = data
  } catch (error) {
    snackbar.error('No se pudo cargar la propiedad')
    console.error('Error detalle:', error)
    goBack()
  }
}

const updateProperty = async (formData) => {
  isSubmitting.value = true
  try {
    await axios.put(`/api/properties/${propertyId}`, formData)
    router.push('/properties')
  } catch (error) {
    let message = 'Hubo un error al actualizar la propiedad'

    if (error.response) {
      if (error.response.status === 422 && error.response.data?.message) {
        message = error.response.data.message
      } else if (error.response.data?.message) {
        message = error.response.data.message
      } else if (typeof error.response.data === 'string') {
        message = error.response.data
      }
    }

    snackbar.error(message)
    console.error('Error detalle:', error.response?.data || error)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(fetchProperty)
</script>
