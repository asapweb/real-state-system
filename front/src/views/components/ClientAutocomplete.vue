<template>
  <div>
    <v-autocomplete
      :hide-details="hideDetails"
      v-model="selected"
      v-model:search="search"
      :items="items"
      :item-title="getClientLabel"
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
          <v-list-item-title v-if="item.id === '__new__'">
            <v-icon start class="me-2">mdi-plus</v-icon>
            Crear nuevo cliente "{{ search }}"
          </v-list-item-title>
          <template v-else>
            <v-list-item-title>{{ item.full_name }}</v-list-item-title>
            <v-list-item-subtitle v-if="item.document_number">
              {{ item.document_type?.name || 'Tipo no especificado' }} {{ item.document_number }}
            </v-list-item-subtitle>
            <v-list-item-subtitle v-else> (sin documento) </v-list-item-subtitle>
          </template>
        </v-list-item>
      </template>
    </v-autocomplete>

    <!-- Diálogo con ClientForm -->
    <v-dialog v-model="dialog" max-width="700px">
      <v-card title="Crear Cliente">
        <ClientForm
          :initial-data="null"
          :loading="saving"
          @submit="handleCreateClient"
          @cancel="dialog = false"
        />
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from '@/services/axios'
import { debounce } from 'lodash'
import ClientForm from '@/views/clients/components/ClientForm.vue'

const props = defineProps({
  modelValue: [Number, Object, null],
  errorMessages: Array,
  hideDetails: { type: [String, Boolean], default: 'auto' },
  flat: { type: Boolean, default: false },
  variant: { type: String, default: 'solo-filled' },
  label: { type: String, default: 'Cliente' },
})
const emit = defineEmits(['update:modelValue', 'blur', 'change'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)
const getClientLabel = (item) => {
  if (!item || item.id === '__new__') return item?.full_name || ''
  const name = item.full_name || ''
  const doc = item.document_number
    ? `${item.document_type?.name || ''} ${item.document_number}`
    : '(sin documento)'
  return `${name} / ${doc}`
}

const fetchClients = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get('/api/clients', {
      params: { 'search[name]': query },
    })

    const results = data.data.map((client) => ({
      ...client,
      full_name: `${client.name ?? ''} ${client.last_name ?? ''}`.trim(),
    }))

    items.value = results.length
      ? results
      : [{ id: '__new__', full_name: `Crear nuevo cliente "${query}"` }]
  } catch (e) {
    console.error('Error buscando clientes:', e)
  } finally {
    loading.value = false
  }
}

const fetchClientById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/clients/${id}`)
    const client = {
      ...data.data,
      full_name: `${data.data.name ?? ''} ${data.data.last_name ?? ''}`.trim(),
    }
    selected.value = client
    items.value = [client]
  } catch (e) {
    console.error('Error cargando cliente:', e)
  }
}

watch(
  () => props.modelValue,
  async (val) => {
    if (typeof val === 'number') {
      await fetchClientById(val)
    } else if (val === null) {
      selected.value = null
      items.value = []
    }
  },
  { immediate: true },
)

watch(selected, (val) => {
  if (val?.id === '__new__') {
    dialog.value = true
    selected.value = null
    emit('update:modelValue', null)
    emit('change', null)
  } else {
    emit('update:modelValue', val?.id ?? null)
    emit('change', val?.id ?? null)
  }
})

watch(
  search,
  debounce((val) => {
    fetchClients(val)
  }, 300),
)

const handleCreateClient = async (formData) => {
  saving.value = true
  try {
    const { data } = await axios.post('/api/clients', formData)
    const created = {
      ...data,
      full_name: `${data.name ?? ''} ${data.last_name ?? ''}`.trim(),
    }

    selected.value = created
    items.value = [created]
    emit('update:modelValue', created.id)
    dialog.value = false
  } catch (e) {
    console.error('Error creando cliente:', e)
  } finally {
    saving.value = false
  }
}
</script>
