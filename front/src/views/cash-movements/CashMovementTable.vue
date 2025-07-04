<template>
  <v-data-table-server
    :headers="headers"
    :items="items"
    :loading="loading"
    :items-per-page="perPage"
    :page="page"
    :server-items-length="total"
    @update:page="onPageChange"
    @update:items-per-page="onPerPageChange"
  >
    <template #top>
      <v-row class="ma-2" dense>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="filters.currency"
            label="Moneda"
            @input="fetchData"
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="filters.date_from"
            label="Desde"
            type="date"
            @input="fetchData"
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="filters.date_to"
            label="Hasta"
            type="date"
            @input="fetchData"
            hide-details
          />
        </v-col>
      </v-row>
    </template>
    <template #item.date="{ item }">
      {{ new Date(item.date).toLocaleDateString() }}
    </template>
    <template #item.amount="{ item }">
      {{ item.amount.toFixed(2) }} {{ item.currency }}
    </template>
    <template #item.payment_method.name="{ item }">
      {{ item.payment_method?.name }}
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/plugins/axios'

const headers = [
  { title: 'Fecha', key: 'date' },
  { title: 'Importe', key: 'amount', align: 'end' },
  { title: 'Medio de Pago', key: 'payment_method.name' },
]

const items = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = ref(10)

const filters = ref({
  currency: '',
  date_from: '',
  date_to: '',
})

async function fetchData() {
  loading.value = true
  const { data } = await axios.get('/cash-movements', {
    params: {
      page: page.value,
      per_page: perPage.value,
      ...filters.value,
    },
  })
  items.value = data.data
  total.value = data.total
  loading.value = false
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
