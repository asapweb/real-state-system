<template>
  <div>
    <div class="mb-8 d-flex align-center">
      <v-btn
        variant="text"
        class="mr-2 rounded-lg"
        height="40"
        width="40"
        min-width="0"
        @click="goBack"
      >
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <h2 class="text-h5">Nueva Solicitud de Alquiler</h2>
    </div>

    <RentalApplicationForm :loading="loading" @submit="handleSubmit" @cancel="goBack" />

    <v-snackbar v-model="snackbar.show" :timeout="6000" color="red" top>
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import RentalApplicationForm from './components/RentalApplicationForm.vue'

const router = useRouter()
const loading = ref(false)
const snackbar = ref({ show: false, message: '' })

const goBack = () => {
  router.push({ name: 'RentalApplicationIndex' })
}

const handleSubmit = async (formData) => {
  loading.value = true
  try {
    await axios.post('/api/rental-applications', formData)
    router.push({ name: 'RentalApplicationIndex' })
  } catch (error) {
    snackbar.value.message = 'Ocurrió un error al guardar la solicitud.'
    snackbar.value.show = true
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>
