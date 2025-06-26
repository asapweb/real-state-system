<template>
  <div class="mb-8">
    <div class="d-flex justify-space-between align-center">
      <h2>Contratos</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/contracts/create')">
        Crear Contrato
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Representan acuerdos de alquiler entre la inmobiliaria y los inquilinos.<br />
      Incluyen fechas, condiciones, clientes involucrados, ajustes, comisiones y penalidades. Fuente
      principal para generar cobranzas y liquidaciones.
    </p>
  </div>

  <v-row>
    <v-col cols="12" class="mb-4">
      <ContractTable
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
import axios from '@/services/axios'
import ContractTable from './components/ContractTable.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const router = useRouter()
const snackbar = useSnackbar()
const tableRef = ref(null)

const handleEdit = (contract) => {
  router.push(`/contracts/${contract.id}/edit`)
}

const handleDelete = async (contract) => {
  try {
    await axios.delete(`/api/contracts/${contract.id}`)
    snackbar.success('Contrato eliminado correctamente')
    tableRef.value?.reload()
  } catch (error) {
    console.error(error)
    snackbar.error('No se pudo eliminar el contrato')
  }
}

const handleError = (message) => {
  snackbar.error(message)
}
</script>
