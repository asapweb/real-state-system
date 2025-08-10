<template>
  <div>
    <!-- Tabla de gastos -->
    <ContractExpenseTable
      ref="expenseTable"
      :contract-id="contractId"
      @edit-expense="editExpense"
      @delete-expense="confirmDelete"
    />

    <!-- Formulario alta/edición -->
    <v-dialog v-model="showForm" max-width="600px">
      <ContractExpenseForm
        :initial-data="selectedExpense"
        :contract-id="contractId"
        :loading="saving"
        @submit="handleSave"
        @cancel="closeForm"
      />
    </v-dialog>

    <!-- Confirmación eliminación -->
    <v-dialog v-model="showDeleteConfirm" max-width="400">
      <v-card>
        <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
        <v-card-text>¿Seguro que deseas eliminar este gasto?</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn text @click="showDeleteConfirm = false">Cancelar</v-btn>
          <v-btn color="error" @click="deleteExpense" :loading="deleting">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractExpenseTable from './ContractExpenseTable.vue'
import ContractExpenseForm from './ContractExpenseForm.vue'

const props = defineProps({
  contractId: { type: Number, required: false, default: null }, // ✅ Opcional ahora
})

const snackbar = useSnackbar()

// Refs
const expenseTable = ref(null)
const showForm = ref(false)
const selectedExpense = ref(null)
const saving = ref(false)
const showDeleteConfirm = ref(false)
const expenseToDelete = ref(null)
const deleting = ref(false)

// Métodos
const openForm = () => { selectedExpense.value = null; showForm.value = true }
const editExpense = (expense) => { selectedExpense.value = expense; showForm.value = true }
const closeForm = () => { showForm.value = false; selectedExpense.value = null }

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (formData.id) {
      await axios.put(`/api/contract-expenses/${formData.id}`, formData)
      snackbar.success('Gasto actualizado correctamente')
    } else {
      await axios.post('/api/contract-expenses', formData)
      snackbar.success('Gasto creado correctamente')
    }
    closeForm()
    expenseTable.value.fetchExpenses()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al guardar gasto')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (expense) => {
  expenseToDelete.value = expense
  showDeleteConfirm.value = true
}

const deleteExpense = async () => {
  if (!expenseToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/contract-expenses/${expenseToDelete.value.id}`)
    snackbar.success('Gasto eliminado')
    expenseTable.value.fetchExpenses()
  } catch (e) {
    snackbar.error('Error al eliminar gasto')
  } finally {
    deleting.value = false
    showDeleteConfirm.value = false
    expenseToDelete.value = null
  }
}

// Exponer métodos al padre
defineExpose({ openForm })
</script>
