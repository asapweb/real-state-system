<template>
  <v-dialog v-model="internalDialog" max-width="400px">
    <v-card>
      <v-card-title class="text-h6">Registrar pago</v-card-title>
      <v-card-text>
        <v-form @submit.prevent="submitPayment">
          <v-text-field
            v-model="paidAt"
            label="Fecha de pago"
            type="date"
            variant="solo-filled"
            :error-messages="v$.paidAt.$errors.map(e => e.$message)"
          />
          <v-alert v-if="!hasAttachments" type="warning" class="mt-2">
            Este gasto no tiene comprobantes cargados. Recomendamos adjuntar uno antes de validar.
          </v-alert>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="close">Cancelar</v-btn>
        <v-btn color="primary" :loading="loading" @click="submitPayment">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required } from '@vuelidate/validators'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  expense: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const snackbar = useSnackbar()

// Estado interno
const internalDialog = ref(props.modelValue)
watch(() => props.modelValue, (val) => (internalDialog.value = val))
watch(internalDialog, (val) => emit('update:modelValue', val))

const paidAt = ref('')
const loading = ref(false)
const rules = { paidAt: { required } }
const v$ = useVuelidate(rules, { paidAt })

const hasAttachments = computed(() => props.expense?.attachments?.length > 0)

watch(
  () => props.expense,
  (val) => {
    paidAt.value = val?.paid_at || ''
  },
  { immediate: true }
)

const submitPayment = async () => {
  const valid = await v$.value.$validate()
  if (!valid) return

  try {
    loading.value = true
    await axios.post(`/api/contract-expenses/${props.expense.id}/register-payment`, {
      paid_at: paidAt.value
    })
    snackbar.success('Pago registrado correctamente')
    emit('saved')
    close()
  } catch (e) {
    snackbar.error(e.response?.data?.message || 'Error al registrar pago')
  } finally {
    loading.value = false
  }
}

const close = () => {
  internalDialog.value = false
}
</script>
