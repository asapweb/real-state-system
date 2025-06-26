<template>
  <div class="mb-8">
    <div class="d-flex justify-space-between align-center">
      <h2>Propiedades</h2>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="router.push('/properties/create')"
      >
        Crear Propiedad
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1" >
      Inmuebles administrados por la inmobiliaria. <br>
      Incluyen datos generales, ubicación, propietarios, servicios, estado y documentación.
    </p>
  </div>
  <v-row>
    <v-col cols="12" class="mb-4">
      <PropertyTable
        ref="tableRef"
        @edit-property="handleEdit"
        @delete-property="handleDelete"
        @error="handleError"
      />
    </v-col>
  </v-row>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PropertyTable from './components/PropertyTable.vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const snackbar = useSnackbar()
const tableRef = ref(null)

const handleEdit = (property) => {
  router.push(`/properties/${property.id}/edit`)
}

const handleDelete = async (property) => {
  try {
    await axios.delete(`/api/properties/${property.id}`)
    snackbar.success('Propiedad eliminada correctamente')
    tableRef.value?.reload()
  } catch (error) {
    console.error(error)
    snackbar.error('No se pudo eliminar la propiedad')
  }
}

const handleError = (message) => {
  snackbar.error(message)
}
</script>
