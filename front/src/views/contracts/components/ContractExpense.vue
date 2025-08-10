<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Gastos del contrato</span>
      <v-spacer></v-spacer>
      <v-btn
        class="text-none"
        color="primary"
        text="Agregar Gasto"
        variant="text"
        slim
        prepend-icon="mdi-plus"
        @click="expenseManagerRef.openForm()"
      />
    </v-card-title>

    <v-divider />

      <ContractExpenseManager ref="expenseManagerRef" :contract-id="contractId" />

  </v-card>
</template>

<script setup>
import { ref } from 'vue'
import ContractExpenseManager from '@/views/contract-expenses/components/ContractExpenseManager.vue'

const expenseManagerRef = ref(null)

// Suponiendo que `contract` ya está cargado en este contexto:
const props = defineProps({
  contractId: { type: Number, required: true }, // ✅ Opcional ahora
})
</script>
