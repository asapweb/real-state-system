<template>
  <v-autocomplete
    v-model="selectedValue"
    :items="vouchers"
    :loading="loading"
    :search-input.sync="search"
    item-title="full_number"
    item-value="id"
    :placeholder="placeholder"
    :density="density"
    :variant="variant"
    :hide-details="hideDetails"
    clearable
    @update:model-value="onSelectionChange"
    @update:search="onSearchChange"
  >
    <template #item="{ item, props }">
      <v-list-item v-bind="props">
        <template #title>
          <div class="d-flex justify-space-between align-center">
            <span>{{ item.raw.full_number }}</span>
            <span class="text-caption text-grey">{{ formatCurrency(item.raw.pending) }}</span>
          </div>
        </template>
        <template #subtitle>
          <div class="d-flex justify-space-between">
            <span>{{ item.raw.issue_date }}</span>
            <span class="text-caption">Pendiente: {{ formatCurrency(item.raw.pending) }}</span>
          </div>
        </template>
      </v-list-item>
    </template>
  </v-autocomplete>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null
  },
  clientId: {
    type: [Number, String],
    required: true
  },
  currency: {
    type: String,
    default: 'ARS'
  },
  voucherTypeShortName: {
    type: String,
    default: null
  },
  letter: {
    type: String,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Buscar comprobante'
  },
  density: {
    type: String,
    default: 'default'
  },
  variant: {
    type: String,
    default: 'outlined'
  },
  hideDetails: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const selectedValue = ref(props.modelValue)
const vouchers = ref([])
const loading = ref(false)
const search = ref('')

watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue
})

watch(selectedValue, (newValue) => {
  emit('update:modelValue', newValue)
})

function onSelectionChange(value) {
  emit('update:modelValue', value)
}

function onSearchChange(value) {
  if (value) {
    fetchVouchers(value)
  }
}

async function fetchVouchers(searchTerm = '') {
  if (!props.clientId) return

  loading.value = true
  try {
    const params = {
      client_id: props.clientId,
      currency: props.currency,
      per_page: 20
    }

    if (searchTerm) {
      params.search = searchTerm
    }

    if (props.voucherTypeShortName) {
      params.voucher_type_short_name = props.voucherTypeShortName
    }

    if (props.letter) {
      params.letter = props.letter
    }

    const { data } = await axios.get('/api/vouchers/with-pending-by-client', { params })
    vouchers.value = data.data || data
  } catch (error) {
    console.error('Error fetching vouchers:', error)
    vouchers.value = []
  } finally {
    loading.value = false
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: props.currency
  }).format(value || 0)
}

onMounted(() => {
  fetchVouchers()
})
</script>
