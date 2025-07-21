<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Gestión de Valores de Índice</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog(null)">
        Nuevo Valor
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Administra los valores de los diferentes tipos de índice según su modo de cálculo.
    </p>
  </div>

  <!-- Tabla con filtros -->
  <IndexValueTable
    :loading="loading"
    @create="openDialog(null)"
    @edit="openDialog"
    @delete="confirmDelete"
    @update:options="handleOptionsUpdate"
  />

  <!-- Formulario embebido -->
  <v-dialog v-model="dialog" max-width="600px">
    <IndexValueForm
      :initial-data="selectedIndexValue"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Confirmación de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que querés eliminar el valor de índice para {{ indexValueToDelete?.index_type?.name }} - {{ formatIndexValue(indexValueToDelete) }}?
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteIndexValue">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref } from 'vue'
import IndexValueTable from './components/IndexValueTable.vue'
import IndexValueForm from './components/IndexValueForm.vue'
import indexValueService from '@/services/indexValueService'
import { useSnackbar } from '@/composables/useSnackbar'

const snackbar = useSnackbar()

const dialog = ref(false)
const dialogDelete = ref(false)
const selectedIndexValue = ref(null)
const indexValueToDelete = ref(null)
const loading = ref(false)
const saving = ref(false)

const formatIndexValue = (indexValue) => {
  return indexValueService.formatIndexValue(indexValue)
}

const openDialog = (item) => {
  selectedIndexValue.value = item
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedIndexValue.value = null
}

const handleSave = async (formData) => {
  console.log('formData', formData)
  saving.value = true
  try {
    if (formData.id) {
      await indexValueService.updateIndexValue(formData.id, formData)
    } else {
      await indexValueService.createIndexValue(formData)
    }
    snackbar.success('Valor de índice guardado correctamente')
    dialog.value = false
    // Emitir evento para refrescar la tabla
    window.dispatchEvent(new CustomEvent('refresh-index-values'))
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al guardar valor de índice'
    snackbar.error(errorMessage)
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  indexValueToDelete.value = item
  dialogDelete.value = true
}

const deleteIndexValue = async () => {
  try {
    await indexValueService.deleteIndexValue(indexValueToDelete.value.id)
    snackbar.success('Valor de índice eliminado')
    // Emitir evento para refrescar la tabla
    window.dispatchEvent(new CustomEvent('refresh-index-values'))
  } catch (e) {
    console.error(e)
    const errorMessage = e.response?.data?.message || 'Error al eliminar valor de índice'
    snackbar.error(errorMessage)
  } finally {
    dialogDelete.value = false
    indexValueToDelete.value = null
  }
}

const handleOptionsUpdate = (options) => {
  // Aquí podrías manejar actualizaciones de opciones si es necesario
  console.log('Options updated:', options)
}
</script>
