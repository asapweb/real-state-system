<template>
  <div class="pa-2 pa-md-4">
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 mb-1">Editar cargo</h1>
        <p class="text-body-2 text-medium-emphasis mb-0" v-if="charge">
          {{ headerSubtitle }}
        </p>
        <v-chip
          v-if="charge?.is_canceled"
          size="small"
          color="error"
          variant="tonal"
          class="mt-2"
        >
          Cancelado
        </v-chip>
      </div>
      <div class="d-flex align-center ga-2">
        <v-btn
          color="error"
          variant="tonal"
          v-if="canCancel"
          @click="openCancelDialog"
        >
          <v-icon size="18" class="me-1">mdi-close-circle</v-icon>
          Cancelar cargo
        </v-btn>
        <v-btn color="primary" variant="text" :to="{ name: 'ContractChargeCreate' }">
          <v-icon size="18" class="me-1">mdi-plus</v-icon>
          Nuevo cargo
        </v-btn>
      </div>
    </div>

    <v-skeleton-loader v-if="loading" type="article" />

    <ContractChargeForm
      v-else-if="charge"
      :initial-data="charge"
      :loading="saving"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />

    <v-alert v-else type="error" variant="tonal">
      No pudimos cargar el cargo solicitado.
    </v-alert>
  </div>

  <v-dialog v-model="cancelDialog" max-width="520">
    <v-card>
      <v-card-title class="text-h6">Cancelar cargo</v-card-title>
      <v-card-text>
        <p class="text-body-2 mb-4">
          Confirmá la cancelación del cargo <strong>{{ cancelDialogTitle }}</strong>.
        </p>
        <v-textarea
          v-model="cancelReason"
          label="Motivo de cancelación"
          auto-grow
          rows="2"
          variant="solo-filled"
          :error-messages="cancelReasonErrors"
          :disabled="canceling"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="canceling" @click="closeCancelDialog">Cerrar</v-btn>
        <v-btn color="error" :loading="canceling" :disabled="cancelReasonErrors.length > 0" @click="confirmCancellation">
          Cancelar cargo
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractChargeForm from './components/ContractChargeForm.vue'
import { formatDate } from '@/utils/date-formatter'
import { formatModelId } from '@/utils/models-formatter'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()

const id = computed(() => Number(route.params.id))
const charge = ref(null)
const loading = ref(false)
const saving = ref(false)
const cancelDialog = ref(false)
const cancelReason = ref('')
const canceling = ref(false)

const normalizePayload = (payload) => {
  if (!payload) return null
  return payload.data ?? payload
}

const loadCharge = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contract-charges/${id.value}`)
    charge.value = normalizePayload(data)
  } catch (error) {
    console.error(error)
    snackbar?.error('No se pudo cargar el cargo')
    charge.value = null
  } finally {
    loading.value = false
  }
}

const handleSubmit = async (payload) => {
  saving.value = true
  try {
    await axios.put(`/api/contract-charges/${id.value}`, payload)
    snackbar?.success('Cargo actualizado correctamente')
    router.push({ name: 'ContractChargeIndex' })
  } catch (error) {
    console.error(error)
    const message = error.response?.data?.message || 'No pudimos actualizar el cargo'
    snackbar?.error(message)
  } finally {
    saving.value = false
  }
}

const handleCancel = () => {
  router.push({ name: 'ContractChargeIndex' })
}

const canCancel = computed(() => {
  if (!charge.value) return false
  if (charge.value.is_canceled) return false
  if (typeof charge.value.can_cancel === 'boolean') return charge.value.can_cancel
  return true
})

const headerSubtitle = computed(() => {
  if (!charge.value) return ''
  const pieces = []
  pieces.push(formatModelId(charge.value.id, 'CC'))
  if (charge.value.contract_id) {
    pieces.push(`Contrato ${formatModelId(charge.value.contract_id, 'CON')}`)
  }
  if (charge.value.effective_date) {
    pieces.push(`Período ${formatDate(charge.value.effective_date)}`)
  }
  return pieces.join(' · ')
})

const cancelReasonErrors = computed(() => {
  if (!cancelDialog.value) return []
  return cancelReason.value.trim().length >= 3
    ? []
    : ['Ingresá un motivo (mínimo 3 caracteres).']
})

const cancelDialogTitle = computed(() => {
  if (!charge.value) return ''
  return formatModelId(charge.value.id, 'CC')
})

const openCancelDialog = () => {
  cancelReason.value = ''
  cancelDialog.value = true
}

const closeCancelDialog = () => {
  cancelDialog.value = false
  cancelReason.value = ''
}

const confirmCancellation = async () => {
  if (!charge.value || cancelReasonErrors.value.length > 0) return
  canceling.value = true
  try {
    const { data } = await axios.post(`/api/contract-charges/${id.value}/cancel`, {
      reason: cancelReason.value.trim(),
    })
    charge.value = normalizePayload(data)
    snackbar?.success('Cargo cancelado correctamente')
    closeCancelDialog()
  } catch (error) {
    console.error(error)
    const message = error.response?.data?.message || 'No se pudo cancelar el cargo'
    snackbar?.error(message)
  } finally {
    canceling.value = false
  }
}

onMounted(loadCharge)
</script>
