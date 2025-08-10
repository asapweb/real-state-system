<template>
  <div>
    <ContractCollectionsHeader
      :contract="contract"
      :period="period"
      @changePeriod="onChangePeriod"
      @generate="generateCobranza"
    />

    <ContractCollectionsTable v-if="contract"
      :contractId="contract.id"
      :period="period"
      @refresh="loadData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import ContractCollectionsHeader from './components/ContractCollectionsHeader.vue'
import ContractCollectionsTable from './components/ContractCollectionsTable.vue'
import { useSnackbar } from '@/composables/useSnackbar'
import { generateCobranzaForContract } from '@/services/collections'
import axios from '@/services/axios'

const route = useRoute()
const contract = ref(null)
const period = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM
const snackbar = useSnackbar()

function onChangePeriod(newPeriod) {
  period.value = newPeriod
}

async function generateCobranza() {
  try {
    await generateCobranzaForContract(contract.value.id, period.value)
    snackbar.success('Cobranza generada exitosamente')
  } catch (error) {
    snackbar.error('Error al generar la cobranza')
  }
}


function loadData() {
  // Placeholder for future refresh logic if needed
}
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
})
</script>
