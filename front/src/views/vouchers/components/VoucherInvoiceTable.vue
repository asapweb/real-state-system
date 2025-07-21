<template>
      <!-- Filtros -->
      <v-row dense class="mb-2">
        <v-col cols="12" md="3">
          <v-text-field
            v-model="internalFilters.search"
            label="Buscar por número o cliente"
            density="compact"
            clearable
            @input="onFilterChange"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-autocomplete
            v-model="internalFilters.booklet_id"
            :items="booklets"
            item-title="name"
            item-value="id"
            label="Talonario"
            clearable
            density="compact"
            @update:modelValue="onFilterChange"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="internalFilters.status"
            :items="['draft', 'issued', 'cancelled']"
            label="Estado"
            clearable
            density="compact"
            @update:modelValue="onFilterChange"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="internalFilters.date_from"
            label="Desde"
            type="date"
            density="compact"
            @change="onFilterChange"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            v-model="internalFilters.date_to"
            label="Hasta"
            type="date"
            density="compact"
            @change="onFilterChange"
          />
        </v-col>
      </v-row>

      <!-- Tabla -->
      <v-data-table-server
        :headers="headers"
        :items="vouchers"
        :items-length="total"
        :loading="loading"
        :items-per-page="perPage"
        :page.sync="page"
        @update:options="onOptionsUpdate"
      >
        <template #item.full_number="{ item }">
          <a href="#" class="font-weight-medium text-decoration-none" @click.prevent="$emit('show', item.id)">
            {{ item.full_number }}
          </a>
        </template>
        <template #item.total="{ item }">
          {{ formatMoney(item.total, item.currency) }}
        </template>
        <template #item.issue_date="{ item }">
          {{ formatDate(item.issue_date) }}
        </template>
        <template #item.due_date="{ item }">
          {{ formatDate(item.due_date) }}
        </template>

      </v-data-table-server>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import debounce from 'lodash/debounce'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'

const props = defineProps({
  filters: Object
})
const emit = defineEmits(['show', 'edit'])

const headers = [
  { title: 'Comprobante', value: 'full_number' },
  { title: 'Cliente', value: 'client_name' },
  { title: 'Fecha', value: 'issue_date' },
  { title: 'Vencimiento', value: 'due_date' },
  { title: 'Total', value: 'total' },
  { title: 'Estado', value: 'status' },
  { title: '', value: 'actions', sortable: false }
]

const vouchers = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = 10

const booklets = ref([])

const internalFilters = reactive({
  search: '',
  booklet_id: null,
  status: null,
  date_from: null,
  date_to: null
})

watch(() => props.filters, fetchData, { deep: true })
watch(() => internalFilters.search, debounce(fetchData, 400))

function onOptionsUpdate(options) {
  page.value = options.page
  fetchData()
}

function onFilterChange() {
  page.value = 1
  fetchData()
}

async function fetchBooklets() {
  const { data } = await axios.get('/api/booklets', {
    params: { type: 'FAC', active: true }
  })
  booklets.value = data.data || data
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/vouchers', {
      params: {
        voucher_type: 'FAC',
        page: page.value,
        per_page: perPage,
        search: internalFilters.search,
        booklet_id: internalFilters.booklet_id,
        status: internalFilters.status,
        date_from: internalFilters.date_from,
        date_to: internalFilters.date_to,
      }
    })
    vouchers.value = data.data
    total.value = data.meta.total
  } finally {
    loading.value = false
  }
}

fetchBooklets()
fetchData()
</script>
