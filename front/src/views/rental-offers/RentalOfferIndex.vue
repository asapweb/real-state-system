<template>
  <div class="mb-8">
    <div class="d-flex justify-space-between align-center">
      <h2>Ofertas de Alquiler</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/rental-offers/create')">
        Crear Oferta
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Representan inmuebles en condiciones de ser alquilados.<br />
      Incluyen condiciones comerciales: precio, duración, garantías, requisitos.
    </p>
  </div>

  <v-row>
    <v-col cols="12" class="mb-4">
      <RentalOfferTable
        ref="tableRef"
        @edit="handleEdit"
        @delete="handleDelete"
        @error="handleError"
      />
    </v-col>
  </v-row>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import RentalOfferTable from './components/RentalOfferTable.vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const snackbar = useSnackbar()
const tableRef = ref(null)

const handleEdit = (offer) => {
  router.push(`/rental-offers/${offer.id}/edit`)
}

const handleDelete = async (offer) => {
  try {
    await axios.delete(`/api/rental-offers/${offer.id}`)
    snackbar.success('Oferta eliminada correctamente')
    tableRef.value?.reload()
  } catch (error) {
    console.error(error)
    snackbar.error('No se pudo eliminar la oferta')
  }
}

const handleError = (message) => {
  snackbar.error(message)
}
</script>
