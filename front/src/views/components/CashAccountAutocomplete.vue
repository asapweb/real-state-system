<template>
  <v-autocomplete
    v-model="selected"
    v-model:search="search"
    :items="items"
    :item-title="getAccountLabel"
    item-value="id"
    label="Cuenta de caja"
    clearable
    return-object
    :loading="loading"
    :no-filter="true"
    @blur="emit('blur')"
    :error-messages="errorMessages"
  >
  </v-autocomplete>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from '@/services/axios'
import { debounce } from 'lodash'

const props = defineProps({
  modelValue: [Number, Object, null],
  errorMessages: Array,
})
const emit = defineEmits(['update:modelValue', 'blur', 'selected'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)

const getAccountLabel = (item) => {
  if (!item) return ''
  return `${item.name ?? ''} (${item.currency ?? ''})`
}

const fetchAccounts = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }
  loading.value = true
  try {
    const { data } = await axios.get('/api/cash-accounts', {
      params: { 'search[name]': query, per_page: 10 },
    })
    items.value = data.data
  } catch (e) {
    console.error('Error buscando cuentas de caja:', e)
  } finally {
    loading.value = false
  }
}

const fetchAccountById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/cash-accounts/${id}`)
    selected.value = data.data
    items.value = [data.data]
    emit('selected', data.data)
  } catch (e) {
    console.error('Error cargando cuenta de caja:', e)
  }
}

watch(
  () => props.modelValue,
  async (val) => {
    if (typeof val === 'number') {
      await fetchAccountById(val)
    } else if (val === null) {
      selected.value = null
      items.value = []
    }
  },
  { immediate: true },
)

watch(selected, (val) => {
  emit('update:modelValue', val?.id ?? null)
  if (val) emit('selected', val)
})

watch(
  search,
  debounce((val) => {
    fetchAccounts(val)
  }, 300),
)
</script>
