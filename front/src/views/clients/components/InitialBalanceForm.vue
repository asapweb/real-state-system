<template>
  <v-form @submit.prevent="submit" class="d-flex flex-column gap-4" style="max-width: 400px">
    <v-select
      v-model="form.currency"
      :items="currencies"
      label="Moneda"
      required
    />
    <v-text-field
      v-model="form.amount"
      label="Saldo inicial"
      type="number"
      required
    />
    <v-btn color="primary" type="submit" :loading="loading">Guardar</v-btn>
  </v-form>
</template>

<script setup>
import { ref } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps({ clientId: Number })
const snackbar = useSnackbar()
const loading = ref(false)

const currencies = ['ARS', 'USD']
const form = ref({ currency: '', amount: 0 })

async function submit() {
  loading.value = true
  try {
    await axios.post(`/api/clients/${props.clientId}/account-movements/initial-balance`, form.value)
    snackbar.success('Saldo inicial guardado')
  } catch (error) {
    snackbar.error(error.response?.data?.error || 'Error al guardar')
  } finally {
    loading.value = false
  }
}
</script>
