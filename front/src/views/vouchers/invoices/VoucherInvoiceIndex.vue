<template>
<div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Facturas</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="goToCreate">
        Nueva Factura
      </v-btn>
    </div>
  </div>

  <VoucherInvoiceTable
    :filters="filters"
    @edit="goToEdit"
    @show="goToShow"
  />

</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import VoucherLayout from '@/views/vouchers/components/VoucherLayout.vue'
import VoucherInvoiceTable from '@/views/vouchers/components/VoucherInvoiceTable.vue'

const router = useRouter()

const filters = reactive({
  search: '',
  client_id: null,
  period: null,
  status: null
})

function goToEdit(id) {
  router.push(`/vouchers/invoices/${id}/edit`)
}

function goToCreate() {
  router.push({ name: 'VoucherInvoiceCreate' })
}

function goToShow(id) {
  router.push(`/vouchers/invoices/${id}`)
}
</script>
