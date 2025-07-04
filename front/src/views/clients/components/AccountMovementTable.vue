<template>
  <v-data-table-server
    :headers="headers"
    :items="items"
    :items-length="total"
    :loading="loading"
    :items-per-page="perPage"
    :page="page"
    @update:page="onPageChange"
    @update:items-per-page="onPerPageChange"
  >
    <template #top>
      <v-select
        v-model="filters.currency"
        :items="currencies"
        label="Moneda"
        class="ma-2"
        clearable
        hide-details
      />
    </template>

    <template #item.date="{ item }">
      {{ item.date ? new Date(item.date).toLocaleDateString() : '' }}
    </template>

    <template #item.description="{ item }">
      {{ item.description || '-' }}
    </template>

    <template #item.amount="{ item }">
      {{ formatMoney(item.amount, item.currency) }}
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/services/axios'
import { formatMoney } from '@/utils/money'

const props = defineProps({ clientId: Number })

const headers = [
  { title: 'Fecha', key: 'date' },
  { title: 'Descripción', key: 'description' },
  { title: 'Importe', key: 'amount', align: 'end' },
]

const items = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)

const currencies = ['ARS', 'USD']
const filters = ref({
  currency: '',
})

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/clients/${props.clientId}/account-movements`, {
      params: {
        page: page.value,
        per_page: perPage.value,
        currency: filters.value.currency || undefined,
      },
    })
    items.value = data.data
    total.value = data.meta.total
  } catch (error) {
    console.error('Error fetching account movements:', error)
  } finally {
    loading.value = false
  }
}

function onPageChange(p) {
  page.value = p
  fetchData()
}

function onPerPageChange(p) {
  perPage.value = p
  fetchData()
}

onMounted(fetchData)
watch(filters, fetchData, { deep: true })
</script>
