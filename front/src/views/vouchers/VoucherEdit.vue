<template>
  <div class="mb-8">
    <div class="mb-1 d-flex justify-space-between align-center">
      <h2>Editar Voucher</h2>
    </div>
    <VoucherForm
      v-if="voucher"
      :voucher="voucher"
      @saved="handleSaved"
    />
    <v-card v-else-if="loading" class="mb-4">
      <v-card-text class="text-center">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
        <p class="mt-2">Cargando voucher...</p>
      </v-card-text>
    </v-card>
    <v-card v-else class="mb-4">
      <v-card-text class="text-center">
        <v-icon size="64" color="error">mdi-alert-circle</v-icon>
        <p class="mt-2">No se pudo cargar el voucher</p>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import voucherService from '@/services/voucherService'
import { useSnackbar } from '@/composables/useSnackbar'
import VoucherForm from './components/VoucherForm.vue'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()

const voucher = ref(null)
const loading = ref(true)

onMounted(() => {
  fetchVoucher()
})

async function fetchVoucher() {
  loading.value = true
  try {
    const { data } = await voucherService.get(route.params.id)
    voucher.value = data.data || data
  } catch (error) {
    console.error('Error fetching voucher:', error)
    snackbar.error('No se pudo cargar el voucher')
  } finally {
    loading.value = false
  }
}

function handleSaved(updatedVoucher) {
  snackbar.success('Voucher actualizado correctamente')
  router.push({ name: 'VoucherShow', params: { id: updatedVoucher.id } })
}
</script>
