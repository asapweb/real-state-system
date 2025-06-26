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
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </v-btn>
      <h2 class="">Solicitud de Alquiler <span class="font-weight-light">/ {{ formatModelId(application?.id, 'SOL') }}</span></h2>
    </div>
  <div>

    <RentalApplicationForm
      v-if="application"
      :initial-data="application"
      :loading="loading"
      @submit="handleSubmit"
      @cancel="goBack"
    />

    <v-snackbar v-model="snackbar.show" :timeout="6000" color="red" top>
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import RentalApplicationForm from './components/RentalApplicationForm.vue'
import { formatModelId } from '@/utils/models-formatter';

const route = useRoute()
const router = useRouter()

const application = ref(null)
const loading = ref(false)
const snackbar = ref({ show: false, message: '' })

const goBack = () => {
  router.push({ name: 'RentalApplicationIndex' })
}

const loadApplication = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/rental-applications/${route.params.id}`)
    application.value = data
  } catch (error) {
    snackbar.value.message = 'No se pudo cargar la solicitud.'
    snackbar.value.show = true
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async (formData) => {
  loading.value = true
  try {
    await axios.put(`/api/rental-applications/${route.params.id}`, formData)
    router.push({ name: 'RentalApplicationIndex' })
  } catch (error) {
    snackbar.value.message = 'Ocurrió un error al actualizar la solicitud.'
    snackbar.value.show = true
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(loadApplication)
</script>
