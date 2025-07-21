<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Nuevo Voucher</h2>
    </div>
    <VoucherForm @saved="handleSaved" />
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import VoucherForm from './components/VoucherForm.vue'

const router = useRouter()

function handleSaved(voucher) {
  router.push({ name: 'VoucherShow', params: { id: voucher.id } })
}
</script>
