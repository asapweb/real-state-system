<template>
  <v-data-table-server
    :headers="headers"
    :items="items"
    :items-length="total"
    :loading="loading"
    class="elevation-1"
    :items-per-page="perPage"
    :page.sync="page"
    item-value="id"
  >
    <template #item.date="{ item }">
      {{ item.date || '-' }}
    </template>

    <template #item.description="{ item }">
      {{ item.description || '-' }}
    </template>

    <template #item.amount="{ item }">
      {{ (item.amount ?? 0).toFixed(2) }} {{ item.currency ?? '' }}
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  clientId: {
    type: Number,
    required: true,
  },
})

const headers = [
  { text: 'Fecha', value: 'date' },
  { text: 'Descripción', value: 'description' },
  { text: 'Importe', value: 'amount', align: 'end' },
]

const items = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = 10

async function fetchData() {
  if (!props.clientId) return
  loading.value = true

  try {
    const response = await axios.get(`/api/clients/${props.clientId}/account-movements`, {
      params: {
        page: page.value,
        per_page: perPage,
      },
    })
    items.value = response.data.data || []
    total.value = response.data.total || 0
  } catch (error) {
    console.error('Error al cargar movimientos de cuenta:', error)
  } finally {
    loading.value = false
  }
}

watch(() => props.clientId, fetchData, { immediate: true })
watch(page, fetchData)
onMounted(fetchData)
</script>
