<template>
  <v-container>
    <v-card>
      <v-card-title>Conceptos pendientes de cobranza</v-card-title>
      <v-card-text>
        <v-tabs v-model="tab" grow>
          <v-tab>Alquileres pendientes</v-tab>
          <v-tab>Gastos no facturados</v-tab>
        </v-tabs>

        <v-tabs-window v-model="tab">
          <!-- Alquileres -->
          <v-tabs-window-item>
            <v-data-table :headers="rentHeaders" :items="missingRents" class="elevation-1" />
          </v-tabs-window-item>

          <!-- Gastos -->
          <v-tabs-window-item>
            <v-data-table :headers="expenseHeaders" :items="unbilledExpenses" class="elevation-1" />
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'

const tab = ref(0)
const missingRents = ref([])
const unbilledExpenses = ref([])

const rentHeaders = [
  { text: 'Contrato', value: 'id' },
  { text: 'Inquilino', value: 'client.name' },
  { text: 'Inicio', value: 'start_date' },
  { text: 'Fin', value: 'end_date' },
  { text: 'Moneda', value: 'currency' },
]

const expenseHeaders = [
  { text: 'Contrato', value: 'contract_id' },
  { text: 'Inquilino', value: 'contract.client.name' },
  { text: 'Descripción', value: 'description' },
  { text: 'Monto', value: 'amount' },
  { text: 'Moneda', value: 'currency' },
]

onMounted(async () => {
  const { data } = await axios.get('/api/contracts/uncollected-concepts')
  missingRents.value = data.missing_rents
  unbilledExpenses.value = data.unbilled_expenses
})
</script>
