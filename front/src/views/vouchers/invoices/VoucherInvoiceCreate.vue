<template>
  <VoucherLayout title="Nueva Factura">
    <VoucherForm :default-type="'fac'" @saved="goToShow" />
  </VoucherLayout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import VoucherForm from '@/views/vouchers/components/VoucherForm.vue'
import VoucherLayout from '@/views/vouchers/components/VoucherLayout.vue'

const router = useRouter()

function goToShow(voucher) {
  router.push(`/vouchers/invoices/${voucher.id}`)
}
</script>
