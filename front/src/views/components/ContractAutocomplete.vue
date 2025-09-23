<template>
  <div>
    <v-autocomplete
      density="compact"
      v-model="selected"
      v-model:search="search"
      :items="items"
      :item-title="getLabel"
      item-value="id"
      :label="label"
      :flat="flat"
      :variant="variant"
      :hide-details="hideDetails"
      :error-messages="errorMessages"
      :loading="loading"
      clearable
      return-object
      :custom-filter="() => true"
      :menu-props="{ maxHeight: 420 }"
      :virtual-scroll="true"
      @blur="emit('blur')"
    >
      <!-- Ítems: 3 renglones, sin duplicados -->
      <template #item="{ item, props }">
        <v-list-item v-bind="props" :title="null" :subtitle="null" lines="three">
          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ getCode(item) }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-body-2">
            {{ getTenant(item) }}
          </v-list-item-subtitle>
          <v-list-item-subtitle class="text-caption text-medium-emphasis">
            {{ getAddress(item) }}
          </v-list-item-subtitle>
        </v-list-item>
      </template>

      <!-- Selección: 1 línea -->
      <template #selection="{ item }">
        <span>
          {{ getCode(item) }} – {{ getTenant(item) }} – {{ getAddress(item) }}
        </span>
      </template>

      <template #no-data>
        <div class="px-4 py-3 text-medium-emphasis">
          Escribí al menos 2 caracteres para buscar contratos…
        </div>
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
  errorMessages: { type: Array, default: () => [] },
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

/* Helper para soportar item.raw en los slots de Vuetify */
const unwrap = (it) => (it && typeof it === 'object' && 'raw' in it ? it.raw : it)

/* Preferí los campos planos del lookup (code/tenant_name/address) */
const getCode = (raw) => {
  const it = unwrap(raw)
  if (!it) return ''
  return it.code ?? `CON-${String(it.id ?? '').padStart(6, '0')}`
}

const getTenant = (raw) => {
  const it = unwrap(raw)
  if (!it) return 'Inquilino no especificado'
  return it.tenant_name
    ?? it.maintenant?.client?.full_name
    ?? (Array.isArray(it.tenants) ? it.tenants.map(t => t?.full_name).filter(Boolean).join(' / ') : undefined)
    ?? it.client?.full_name
    ?? 'Inquilino no especificado'
}

const getAddress = (raw) => {
  const it = unwrap(raw)
  if (!it) return 'Propiedad no especificada'
  if (it.address) return it.address
  const street = it.property?.street ?? 'Propiedad no especificada'
  const number = it.property?.number ? ` ${it.property.number}` : ''
  const extra = [it.property?.neighborhood, it.property?.city].filter(Boolean).join(', ')
  const base = `${street}${number}`
  return extra ? `${base}, ${extra}` : base
}

const getLabel = (raw) => {
  const it = unwrap(raw)
  if (!it) return ''
  return `${getCode(it)} / ${getTenant(it)} / ${getAddress(it)}`
}

/* API */
const fetchContracts = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }
  loading.value = true
  try {
    const { data } = await axios.get('/api/contracts/lookup', {
      params: {
        q: query,
        client_id: props.clientId ?? undefined,
        limit: 15,
      },
    })
    items.value = data?.data ?? []   // 👈 ojo: el cuerpo trae { data: [...] }
  } catch (e) {
    console.error('Error buscando contratos:', e)
    items.value = []
  } finally {
    loading.value = false
  }
}

/* Cargar por id usando el MISMO lookup para conservar el shape */
const fetchContractById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get('/api/contracts/lookup', { params: { id, limit: 1 } })
    const found = data?.data?.[0] ?? null
    selected.value = found
    items.value = found ? [found] : []
  } catch (e) {
    console.error('Error cargando contrato por id:', e)
  }
}

/* Sincroniza con v-model externo */
watch(
  () => props.modelValue,
  async (val) => {
    if (typeof val === 'number') {
      await fetchContractById(val)
    } else if (val && typeof val === 'object' && val.id) {
      selected.value = val
      if (!items.value.some(i => i.id === val.id)) items.value = [val]
    } else if (val === null) {
      selected.value = null
      items.value = []
    }
  },
  { immediate: true },
)

/* Cambia cliente => reset y re-buscar si hay texto */
watch(
  () => props.clientId,
  (val, oldVal) => {
    if (val !== oldVal) {
      selected.value = null
      items.value = []
      emit('update:modelValue', null)
      if (search.value?.length >= 2) debouncedSearch(search.value)
    }
  }
)

/* Emití sólo el id hacia afuera */
watch(selected, (val) => {
  emit('update:modelValue', val?.id ?? null)
})

/* Debounce de búsqueda */
const debouncedSearch = debounce((val) => {
  fetchContracts(val)
}, 300)

watch(search, (val) => debouncedSearch(val))
</script>
