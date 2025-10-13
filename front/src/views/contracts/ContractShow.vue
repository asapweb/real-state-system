<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="goBack"
    >
      <v-icon>mdi-arrow-left</v-icon>
    </v-btn>
    <h2 class="">
      Contrato <span class="font-weight-light">/ {{ formatModelId(contract?.id, 'CON') }}</span>
    </h2>
  </div>

  <div v-if="contract">
    <v-row>
      <v-col cols="12" md="12">
        <ContractInfoCard :contract="contract" @edit="goToEdit" />
      </v-col>
      <v-col cols="12" id="adjustments">
        <ContractAdjustmentTable :contract-id="contract?.id" />
      </v-col>
      <v-col cols="12">
        Gastos?
      </v-col>
      <v-col cols="12" md="12">
        <AttachmentManager :attachable-type="'contract'" :attachable-id="contract.id" />
      </v-col>
      <v-col cols="12">
        <ContractClientTable :contract-id="contract?.id" />
      </v-col>
      <v-col cols="12">
        <ContractServiceTable :contract-id="contract?.id" />
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { formatModelId } from '@/utils/models-formatter'

import ContractInfoCard from './components/ContractInfoCard.vue'
import AttachmentManager from '@/views/components/AttachmentManager.vue'
import ContractServiceTable from './components/ContractServiceTable.vue'
import ContractClientTable from './components/ContractClientTable.vue'
import ContractAdjustmentTable from './components/ContractAdjustmentTable.vue'



const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()
const contract = ref(null)
const selectedPeriod = ref(new Date().toISOString().substring(0, 7)) // Mes actual en formato YYYY-MM

const goBack = () => router.push('/contracts')
const goToEdit = () => router.push(`/contracts/${contract.value.id}/edit`)

const fetchContract = async () => {
  try {
    const { data } = await axios.get(`/api/contracts/${route.params.id}`)
    contract.value = data.data
  } catch (error) {
    snackbar.error('No se pudo cargar el contrato')
    console.error(error)
  }
}

onMounted(async () => {
  await fetchContract()

  await nextTick() // espera a que la vista reaccione al contract cargado

  if (route.hash === '#adjustments') {
    // Espera un frame más para asegurarte que el DOM esté listo
    requestAnimationFrame(() => {
      const el = document.getElementById('adjustments')
      console.log(el)
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' })
      }
    })
  }
})
</script>
