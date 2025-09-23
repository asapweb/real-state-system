<template>
  <div>
    <v-autocomplete
      :hide-details="hideDetails"
      v-model="selected"
      v-model:search="search"
      :items="items"
      :item-title="getLabel"
      item-value="id"
      :label="label"
      :flat="flat"
      :variant="variant"
      clearable
      return-object
      :loading="loading"
      :no-filter="true"
      @blur="emit('blur')"
    >
      <template #item="{ item, props }">
        <v-list-item v-bind="props">
       ddasdas
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
  hideDetails: {
    default: 'auto',
  },
  clientId: [Number, null],
  errorMessages: Array,
  hideDetails: { type: [String, Boolean], default: 'auto' },
  flat: { type: Boolean, default: false },
  variant: { type: String, default: 'solo-filled' },
  label: { type: String, default: 'Contrato' },
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
    selected.value = data.data
    items.value = [data.data]
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

// También observar cambios en clientId para recargar el contrato si es necesario
watch(
  () => props.clientId,
  async (newClientId, oldClientId) => {
    if (newClientId && props.modelValue && typeof props.modelValue === 'number') {
      // Si cambia el cliente y hay un contrato seleccionado, recargar el contrato
      await fetchContractById(props.modelValue)
    }
  }
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

watch(
  () => props.clientId,
  (val, oldVal) => {
    if (val !== oldVal) {
      selected.value = null
      items.value = []
      emit('update:modelValue', null)
    }
  }
)
</script>
