<template>
  <div class="mb-8 d-flex align-center justify-space-between">
    <div>
      <h2 class="">Gestión de Ajustes de Contrato</h2>
      <p class="text-medium-emphasis mt-1">
        Permite controlar los ajustes pactados en contratos, <br />
        agregando valor a los que aún no lo tienen (como los de tipo índice) y asegurando su impacto
        en las cobranzas.
      </p>
    </div>
    <div class="d-flex">
      <v-btn color="primary" class="ml-2" @click="adjustmentTable.openBulkDialog('assign')">
        <v-icon size="small" class="me-1">mdi-reload</v-icon>
        Asignar índices en lote
      </v-btn>
      <v-btn color="success" class="ml-2" @click="adjustmentTable.openBulkDialog('apply')">
        <v-icon size="small" class="me-1">mdi-check</v-icon>
        Aplicar ajustes en lote
      </v-btn>
      <v-btn color="secondary" class="ml-2" @click="adjustmentTable.openBulkDialog('process')">
        <v-icon size="small" class="me-1">mdi-playlist-check</v-icon>
        Procesar (Asignar + Aplicar)
      </v-btn>
    </div>
  </div>

  <ContractAdjustmentTable ref="adjustmentTable" />
</template>

<script setup>
import { ref } from 'vue'
import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'

const adjustmentTable = ref(null)
</script>
