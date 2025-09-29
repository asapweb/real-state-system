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
    <template #item.contract="{ item }">
      <div class="d-flex flex-column">
        <div class="text-body-2">{{ item.contract?.code || '—' }}</div>
        <div class="text-caption text-medium-emphasis">{{ item.contract?.tenant_name || '' }}</div>
      </div>
    </template>
    <template #item.amount="{ item }">
      <span>{{ formatCurrency(item.amount, item.currency) }}</span>
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
  { title: 'Contrato', key: 'contract', sortable: false },
  { title: 'Fecha', key: 'effective_date', sortable: true },
  { title: 'Importe', key: 'amount', sortable: true },
  { title: 'Moneda', key: 'currency', sortable: true },
  { title: 'Voucher', key: 'voucher_id', sortable: false },
]

const items = ref([])
const totalItems = ref(0)
const loading = ref(false)
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref([{ key: 'effective_date', order: 'asc' }])

function formatCurrency(value, currency) {
  if (typeof value !== 'number') return value
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: currency || 'ARS', maximumFractionDigits: 2 }).format(value)
}

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/dashboard/rents', {
      params: {
        period: props.period,
        currency: props.currency,
        per_page: itemsPerPage.value,
        sort_by: sortBy.value?.[0]?.key || 'effective_date',
        sort_direction: sortBy.value?.[0]?.order || 'asc',
        page: page.value,
      }
    })
    items.value = (data.data || []).map(row => ({
      id: row.id,
      contract: {
        code: row.contract?.code,
        tenant_name: row.contract?.tenant?.full_name || [row.contract?.tenant?.first_name, row.contract?.tenant?.last_name].filter(Boolean).join(' ')
      },
      effective_date: row.effective_date,
      amount: Number(row.amount),
      currency: row.currency,
      voucher_id: row.voucher_id,
    }))
    totalItems.value = data.total || 0
  } finally {
    loading.value = false
  }
}

watch(() => [props.period, props.currency], fetchItems, { immediate: true })
</script>
