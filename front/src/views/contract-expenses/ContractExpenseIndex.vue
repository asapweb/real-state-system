<template>
  <div>
    <!-- Encabezado -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="mb-1">Gastos de contratos</h2>
        <p class="text-medium-emphasis">
          Visualice y gestione los gastos de todos los contratos.
        </p>
      </div>
      <v-btn color="primary" @click="expenseManagerRef.openForm()">
        <v-icon size="small" class="me-1">mdi-plus</v-icon>
        Nuevo gasto
      </v-btn>
    </div>

    <!-- Gestor de gastos -->
    <ContractExpenseManager ref="expenseManagerRef" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ContractExpenseManager from './components/ContractExpenseManager.vue'

const expenseManagerRef = ref(null)
</script>
