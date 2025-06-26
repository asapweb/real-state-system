<template>
  <div>
    <div class="mb-8">
      <div class="d-flex justify-space-between align-center">
        <h2 class="">Solicitudes de Alquiler</h2>
        <v-spacer />
        <v-btn color="primary" @click="goToCreate">
          <v-icon start>mdi-plus</v-icon>
          Nueva Solicitud
        </v-btn>
      </div>
      <p class="text-medium-emphasis mt-1">
        Representan el proceso de postulación de una persona a una oferta o propiedad.<br />
        Permiten capturar garantías, reservas, notas internas y documentos
      </p>
    </div>

    <RentalApplicationTable
      ref="tableRef"
      @edit-application="goToEdit"
      @delete-application="confirmDelete"
      @error="showError"
    />

    <!-- Snackbar para errores -->
    <v-snackbar v-model="snackbar.show" :timeout="6000" color="red" top>
      {{ snackbar.message }}
    </v-snackbar>

    <!-- Diálogo confirmación de eliminación -->
    <v-dialog v-model="dialogDelete" max-width="500">
      <v-card>
        <v-card-title class="text-h5">Confirmar eliminación</v-card-title>
        <v-card-text>
          ¿Estás seguro de que querés eliminar la solicitud? Esta acción no se puede deshacer.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="red" variant="flat" @click="deleteApplication">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import RentalApplicationTable from './components/RentalApplicationTable.vue'

const router = useRouter()
const tableRef = ref(null)

const snackbar = ref({ show: false, message: '' })

const showError = (message) => {
  snackbar.value.message = message
  snackbar.value.show = true
}

const goToCreate = () => {
  router.push({ name: 'RentalApplicationCreate' })
}

const goToEdit = (application) => {
  router.push({ name: 'RentalApplicationEdit', params: { id: application.id } })
}

const dialogDelete = ref(false)
const applicationToDelete = ref(null)

const confirmDelete = (application) => {
  applicationToDelete.value = application
  dialogDelete.value = true
}

const deleteApplication = async () => {
  try {
    await axios.delete(`/api/rental-applications/${applicationToDelete.value.id}`)
    dialogDelete.value = false
    tableRef.value.reload()
  } catch (e) {
    showError('No se pudo eliminar la solicitud.')
    console.error(e)
  }
}
</script>
