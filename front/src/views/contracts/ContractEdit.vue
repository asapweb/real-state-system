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
    <h2 class="">
      Editar Contrato
      <span class="font-weight-light">/ {{ formatModelId(contract?.id, 'CON') }}</span>
    </h2>
  </div>

  <ContractForm
    v-if="contract"
    :initial-data="contract"
    :loading="isSubmitting"
    @submit="update"
    @cancel="goBack"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import ContractForm from './components/ContractForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatModelId } from '@/utils/models-formatter'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()

const contract = ref(null)
const isSubmitting = ref(false)
const id = route.params.id

const goBack = () => router.push('/contracts')

const fetchContract = async () => {
  try {
    const { data } = await axios.get(`/api/contracts/${id}`)
    contract.value = data
  } catch (error) {
    snackbar.error('No se pudo cargar el contrato')
    console.error(error)
    goBack()
  }
}

const update = async (formData) => {
  isSubmitting.value = true
  try {
    await axios.put(`/api/contracts/${id}`, formData)
    router.push(`/contracts/${id}`)
  } catch (error) {
    let message = 'Hubo un error al actualizar el contrato'
    if (error.response?.data?.message) message = error.response.data.message
    snackbar.error(message)
    console.error(error)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(fetchContract)
</script>
