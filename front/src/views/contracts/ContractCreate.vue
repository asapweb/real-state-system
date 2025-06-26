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
    <h2>Crear Contrato</h2>
  </div>

  <ContractForm @submit="save" @cancel="goBack" :loading="isSubmitting" />
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import ContractForm from './components/ContractForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const snackbar = useSnackbar()
const isSubmitting = ref(false)

const save = async (formData) => {
  isSubmitting.value = true
  try {
    const { data } = await axios.post('/api/contracts', formData)
    router.push(`/contracts/${data.id}`)
  } catch (error) {
    let message = 'Hubo un error al crear el contrato'
    if (error.response?.data?.message) message = error.response.data.message
    snackbar.error(message)
    console.error(error)
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => router.push('/contracts')
</script>
