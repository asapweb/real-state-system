<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="mb-1">Editar cargo #{{ id }}</h2>
      </div>
    </div>

    <ContractChargeForm
      :initial-data="charge"
      :contract-id="charge?.contract_id || null"
      :loading="saving || loading"
      @submit="handleSave"
      @cancel="$router.push('/contracts/charges')"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractChargeForm from './components/ContractChargeForm.vue'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()

const id = computed(() => Number(route.params.id))
const charge = ref(null)
const loading = ref(false)
const saving = ref(false)

const loadCharge = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contract-charges/${id.value}`)
    charge.value = data.data
  } catch (e) {
    snackbar.error('No se pudo cargar el cargo')
  } finally {
    loading.value = false
  }
}

const handleSave = async (payload) => {
  saving.value = true
  try {
    await axios.put(`/api/contract-charges/${id.value}`, payload)
    snackbar.success('Cargo actualizado correctamente')
    router.push('/contracts/charges')
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al actualizar el cargo')
  } finally {
    saving.value = false
  }
}

onMounted(loadCharge)
</script>

