<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Vouchers</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="goToCreate">
        Nuevo Voucher
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Comprobantes electrónicos: cobranzas, liquidaciones, notas de crédito, facturas, etc.<br />
      Utiliza los filtros y acciones para gestionar tus vouchers.
    </p>
  </div>
  <v-row>
    <v-col cols="12" class="mb-4">
      <VoucherTable ref="voucherTableRef" />
    </v-col>
  </v-row>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import VoucherTable from './components/VoucherTable.vue'

const voucherTableRef = ref(null)
const router = useRouter()

function goToCreate() {
  router.push({ name: 'VoucherCreate' })
}
</script>
