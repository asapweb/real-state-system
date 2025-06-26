<template>
  <div class="mb-8">
    <h2 class="">Gestión de Ajustes de Contrato</h2>
    <p class="text-medium-emphasis mt-1">
      Permite controlar los ajustes pactados en contratos, <br />
      agregando valor a los que aún no lo tienen (como los de tipo índice) y asegurando su impacto
      en las cobranzas.
    </p>
  </div>
  <ContractAdjustmentTable
    @edit-adjustment="openEditDialog"
    @error="showError"
    ref="adjustmentTable"
  />

  <ContractAdjustmentValueDialog
    v-model="dialog"
    :adjustment="selectedAdjustment"
    @updated="reload"
  />
</template>

<script setup>
import { ref } from 'vue'
import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'
import ContractAdjustmentValueDialog from './components/ContractAdjustmentValueDialog.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const { showError } = useSnackbar()
const snackbar = useSnackbar()
const dialog = ref(false)
const selectedAdjustment = ref(null)
const adjustmentTable = ref(null)

function openEditDialog(adjustment) {
  selectedAdjustment.value = adjustment
  dialog.value = true
}

function reload() {
  snackbar.success('Ajuste actualizado correctamente')
  dialog.value = false
  adjustmentTable.value?.reload()
}
</script>
