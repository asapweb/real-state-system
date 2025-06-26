<template>
  <div>
    <v-autocomplete
      v-model="selected"
      v-model:search="search"
      :items="items"
      item-title="label"
      item-value="id"
      label="Propiedad"
      clearable
      return-object
      :loading="loading"
      :no-filter="true"
      @blur="emit('blur')"
      :error-messages="errorMessages"
    >
      <template #item="{ item, props }">
        <v-list-item v-bind="props">
          <v-list-item-title v-if="item.id === '__new__'">
            <v-icon start class="me-2">mdi-plus</v-icon>
            Crear nueva propiedad "{{ search }}"
          </v-list-item-title>
          <template v-else>
            <v-list-item-title>{{ item.label }}</v-list-item-title>
            <v-list-item-subtitle v-if="item.registry_number">
              Matrícula: {{ item.registry_number }}
            </v-list-item-subtitle>
          </template>
        </v-list-item>
      </template>
    </v-autocomplete>

    <!-- Diálogo con PropertyForm -->
    <v-dialog v-model="dialog" max-width="900px">
      <v-card title="Crear Propiedad">
        <PropertyForm
          :initial-data="null"
          :loading="saving"
          @submit="handleCreateProperty"
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
import PropertyForm from '@/views/properties/components/PropertyForm.vue'

const props = defineProps({
  modelValue: [Number, Object, null],
  errorMessages: Array,
})

const emit = defineEmits(['update:modelValue', 'blur'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)

const fetchProperties = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get('/api/properties', {
      params: { 'search[text]': query },
    })

    const results = data.data.map((p) => ({
      ...p,
      label: `${p.street ?? ''} ${p.number ?? ''}`.trim(),
    }))

    items.value = results.length
      ? results
      : [{ id: '__new__', label: `Crear nueva propiedad "${query}"` }]
  } catch (e) {
    console.error('Error buscando propiedades:', e)
  } finally {
    loading.value = false
  }
}

const fetchPropertyById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/properties/${id}`)
    selected.value = {
      ...data,
      label: `${data.street ?? ''} ${data.number ?? ''}`.trim(),
    }
    items.value = [selected.value]
  } catch (e) {
    console.error('Error cargando propiedad:', e)
  }
}

watch(
  () => props.modelValue,
  async (val) => {
    if (typeof val === 'number') {
      await fetchPropertyById(val)
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
  } else {
    emit('update:modelValue', val?.id ?? null)
  }
})

watch(
  search,
  debounce((val) => {
    fetchProperties(val)
  }, 300),
)

const handleCreateProperty = async (formData) => {
  saving.value = true
  try {
    const { data } = await axios.post('/api/properties', formData)
    const created = {
      ...data,
      label: `${data.street ?? ''} ${data.number ?? ''}`.trim(),
    }

    selected.value = created
    items.value = [created]
    emit('update:modelValue', created.id)
    dialog.value = false
  } catch (e) {
    console.error('Error creando propiedad:', e)
  } finally {
    saving.value = false
  }
}
</script>
