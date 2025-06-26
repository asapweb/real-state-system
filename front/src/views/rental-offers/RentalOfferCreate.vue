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
      <v-icon>mdi-arrow-left</v-icon>
    </v-btn>
    <h2>Crear Oferta de Alquiler</h2>
  </div>

  <RentalOfferForm @submit="save" @cancel="goBack" :loading="isSubmitting" />
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import RentalOfferForm from './components/RentalOfferForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const snackbar = useSnackbar()
const isSubmitting = ref(false)

const save = async (formData) => {
  isSubmitting.value = true
  try {
    await axios.post('/api/rental-offers', formData)
    router.push('/rental-offers')
  } catch (error) {
    let message = 'Hubo un error al crear la oferta'
    if (error.response?.data?.message) message = error.response.data.message
    snackbar.error(message)
    console.error(error)
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => router.push('/rental-offers')
</script>
