<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="mb-1">Nuevo cargo de contrato</h2>
      </div>
    </div>

    <ContractChargeForm
      :initial-data="null"
      :contract-id="contractIdFromQuery"
      :loading="saving"
      @submit="handleSave"
      @cancel="$router.push('/contracts/charges')"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractChargeForm from './components/ContractChargeForm.vue'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()
const saving = ref(false)

const contractIdFromQuery = computed(() => {
  const v = route.query.contract_id
  return v ? Number(v) : null
})

const handleSave = async (payload) => {
  saving.value = true
  try {
    await axios.post('/api/contract-charges', payload)
    snackbar.success('Cargo creado correctamente')
    router.push('/contracts/charges')
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al crear el cargo')
  } finally {
    saving.value = false
  }
}
</script>

