<template>
  <div class="mb-8">
    <h2 class="">Gestión de Ajustes de Contrato</h2>
    <p class="text-medium-emphasis mt-1">
      Permite controlar los ajustes pactados en contratos, <br />
      agregando valor a los que aún no lo tienen (como los de tipo índice) y asegurando su impacto
      en las cobranzas.
    </p>
  </div>

  <ContractAdjustmentTable ref="adjustmentTable" />
</template>

<script setup>
import { ref } from 'vue'
import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'

const adjustmentTable = ref(null)
</script>
