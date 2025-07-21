<template>
  <v-autocomplete
    v-model="selected"
    :items="vouchers"
    :loading="loading"
    item-title="label"
    item-value="id"
    label="Comprobante"
    clearable
    density="compact"
    @update:modelValue="emitSelection"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: [String, Number, null],
  clientId: [String, Number],
  excludeVoucherId: [String, Number, null],
})

const emit = defineEmits(['update:modelValue'])

const vouchers = ref([])
const selected = ref(null)
const loading = ref(false)

watch(() => props.modelValue, (val) => {
  selected.value = val
}, { immediate: true })

watch(() => props.clientId, fetchVouchers, { immediate: true })

async function fetchVouchers() {
  if (!props.clientId) return

  loading.value = true
  try {
    const { data } = await axios.get('/api/vouchers', {
      params: {
        client_id: props.clientId,
        has_balance: true,
        per_page: 100,
      }
    })

    vouchers.value = (data.data || data)
      .filter(v => v.id !== props.excludeVoucherId)
      .map(v => ({
        id: v.id,
        label: `${v.full_number} - ${v.issue_date} - $${v.balance ?? v.total}`,
      }))
  } finally {
    loading.value = false
  }
}

function emitSelection(val) {
  emit('update:modelValue', val)
}
</script>
