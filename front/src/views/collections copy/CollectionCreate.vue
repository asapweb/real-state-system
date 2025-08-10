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
    <h2>Crear Cobranza Manual</h2>
  </div>

  <CollectionForm :loading="loading" @submit="handleSubmit" @cancel="goBack" />
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CollectionForm from './components/CollectionForm.vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const loading = ref(false)
const snackbar = useSnackbar()

const goBack = () => router.back()

const handleSubmit = async (data) => {
  loading.value = true
  try {
    const res = await axios.post('/api/collections', data)
    snackbar.success('Cobranza creada exitosamente')
    router.push(`/collections/${res.data.id}`)
  } catch (err) {
    console.error(err)
    snackbar.error('Error al crear la cobranza')
  } finally {
    loading.value = false
  }
}
</script>
