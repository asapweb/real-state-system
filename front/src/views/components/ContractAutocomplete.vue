<template>
  <div>
    <v-autocomplete
      v-model="selected"
      v-model:search="search"
      :items="items"
      :item-title="getLabel"
      item-value="id"
      label="Contrato"
      clearable
      return-object
      :loading="loading"
      :no-filter="true"
      @blur="emit('blur')"
    >
      <template #item="{ item, props }">
        <v-list-item v-bind="props">
          <v-list-item-title>
            {{ getLabel(item) }}
          </v-list-item-title>
          <v-list-item-subtitle>
            {{ item.property?.street }} {{ item.property?.number || '' }}
          </v-list-item-subtitle>
        </v-list-item>
      </template>
    </v-autocomplete>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: [Number, Object, null],
  clientId: [Number, null],
  errorMessages: Array,
})

const emit = defineEmits(['update:modelValue', 'blur'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)

const getLabel = (item) => {
  if (!item) return ''
  const id = item.id ? `CON-${item.id.toString().padStart(5, '0')}` : ''
  const client = item.client?.full_name || ''
  const property = item.property?.street || 'Propiedad no especificada'
  return `${id} / ${property} / ${client}`
}

const fetchContracts = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get('/api/contracts', {
      params: {
        'search[term]': query,
        ...(props.clientId ? { 'search[client_id]': props.clientId } : {}),
      },
    })

    items.value = data.data
  } catch (e) {
    console.error('Error buscando contratos:', e)
  } finally {
    loading.value = false
  }
}

const fetchContractById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/contracts/${id}`)
    selected.value = data
    items.value = [data]
  } catch (e) {
    console.error('Error cargando contrato:', e)
  }
}

watch(
  () => props.modelValue,
  async (val) => {
    if (typeof val === 'number') {
      await fetchContractById(val)
    } else if (val === null) {
      selected.value = null
      items.value = []
    }
  },
  { immediate: true },
)

watch(selected, (val) => {
  emit('update:modelValue', val?.id ?? null)
})

watch(
  search,
  debounce((val) => {
    fetchContracts(val)
  }, 300),
)
</script>
