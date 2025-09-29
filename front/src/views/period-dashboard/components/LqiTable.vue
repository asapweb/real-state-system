<template>
  <v-data-table-server
    :headers="headers"
    :items="items"
    :items-length="totalItems"
    :loading="loading"
    item-key="id"
    v-model:page="page"
    v-model:items-per-page="itemsPerPage"
    v-model:sort-by="sortBy"
    class="elevation-1"
    @update:options="fetchItems"
  >
    <template #item.total="{ item }">
      <span>{{ formatCurrency(item.total, item.currency) }}</span>
    </template>
    <template #item.applied="{ item }">
      <span>{{ formatCurrency(item.applied, item.currency) }}</span>
    </template>
    <template #item.balance="{ item }">
      <strong :class="item.balance > 0 ? 'text-error' : 'text-success'">
        {{ formatCurrency(item.balance, item.currency) }}
      </strong>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  period: { type: String, required: true },
  currency: { type: String, default: 'ALL' },
})

const headers = [
  { title: '#', key: 'number', sortable: true },
  { title: 'Contrato', key: 'contract_code', sortable: true },
  { title: 'Emitido', key: 'issued_at', sortable: true },
  { title: 'Total', key: 'total', sortable: true },
  { title: 'Aplicado', key: 'applied', sortable: true },
  { title: 'Saldo', key: 'balance', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
]

const items = ref([])
const totalItems = ref(0)
const loading = ref(false)
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref([{ key: 'issued_at', order: 'desc' }])

function formatCurrency(value, currency) {
  if (typeof value !== 'number') return value
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: currency || 'ARS', maximumFractionDigits: 2 }).format(value)
}

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/dashboard/lqi', {
      params: {
        period: props.period,
        currency: props.currency,
        per_page: itemsPerPage.value,
        page: page.value,
      }
    })
    items.value = (data.data || []).map(row => ({
      id: row.id,
      number: row.number,
      contract_code: row.contract_code,
      issued_at: row.issued_at,
      status: row.status,
      items_count: row.items_count,
      total: Number(row.total),
      applied: Number(row.applied),
      balance: Number(row.balance),
      currency: props.currency === 'ALL' ? 'ARS' : props.currency, // ajusta si devuelves moneda por voucher
    }))
    totalItems.value = data.total || 0
  } finally {
    loading.value = false
  }
}

watch(() => [props.period, props.currency], fetchItems, { immediate: true })
</script>
