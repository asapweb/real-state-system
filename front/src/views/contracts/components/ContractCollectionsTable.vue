<template>
  <v-data-table-server
    v-model:items-per-page="perPage"
    :headers="headers"
    :items="vouchers"
    :items-length="total"
    :loading="loading"
    :page.sync="page"
    class="elevation-1"
    @update:options="fetchVouchers"
  >
    <template #item.status="{ item }">
      <v-chip size="small" :color="statusColor(item.status)" variant="elevated">
        {{ item.status }}
      </v-chip>
    </template>

    <template #item.actions="{ item }">
      <v-btn icon @click="$router.push(`/vouchers/${item.id}`)">
        <v-icon>mdi-eye</v-icon>
      </v-btn>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useVouchers } from '@/composables/useVouchers'

const props = defineProps({
  contractId: Number,
  period: String
})

const emit = defineEmits(['refresh'])

const page = ref(1)
const perPage = ref(10)
const loading = ref(false)
const total = ref(0)
const vouchers = ref([])

const headers = [
  { title: 'N°', key: 'full_number' },
  { title: 'Fecha', key: 'issue_date' },
  { title: 'Estado', key: 'status' },
  { title: 'Total', key: 'total', align: 'end' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' }
]

watch(() => props.period, fetchVouchers, { immediate: true })

async function fetchVouchers() {
  loading.value = true
  try {
    console.log('fetchVouchers', props.contractId, props.period)
    const response = await useVouchers().getContractVouchers(props.contractId, props.period)
    console.log('response', response)
    vouchers.value = response.data.data
    total.value = response.data.total
  } finally {
    loading.value = false
  }
}

function statusColor(status) {
  return status === 'issued' ? 'success' : 'grey'
}
</script>
