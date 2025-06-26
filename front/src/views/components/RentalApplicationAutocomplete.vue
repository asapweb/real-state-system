<template>
  <div>
    <v-autocomplete
      v-model="selected"
      v-model:search="search"
      :items="items"
      item-title="label"
      item-value="id"
      label="Solicitud de alquiler"
      clearable
      return-object
      :loading="loading"
      :no-filter="true"
      @blur="emit('blur')"
      :error-messages="errorMessages"
    >
      <template #item="{ item, props }">
        <v-list-item v-bind="props">
          <template v-if="item.id === '__new__'">
            <v-list-item-title>
              <v-icon start class="me-2">mdi-plus</v-icon>
              Crear nueva solicitud "{{ search }}"
            </v-list-item-title>
            <v-list-item-subtitle>
              dasdasdas
            </v-list-item-subtitle>
          </template>
          <template v-else>
            <v-list-item-title>{{ item.label }}</v-list-item-title>
            <v-list-item-subtitle v-if="item.raw.statusLabel">
              {{ item.raw.statusLabel }} · {{ item.raw.property?.street }} {{ item.raw.property?.number }} · {{ formatDate(item.raw.created_at) }}
            </v-list-item-subtitle>
          </template>
        </v-list-item>
      </template>
    </v-autocomplete>

    <!-- Diálogo para crear solicitud -->
    <v-dialog v-model="dialog" max-width="900px">
      <v-card title="Crear Solicitud de Alquiler">
        <RentalApplicationForm
          :initial-data="{ rental_offer_id: defaultOfferId }"
          :loading="saving"
          @submit="handleCreateApplication"
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
import { formatDate } from '@/utils/date-formatter'
import RentalApplicationForm from '@/views/rental-applications/components/RentalApplicationForm.vue'

const props = defineProps({
  modelValue: [Number, Object, null],
  errorMessages: Array,
  defaultOfferId: { type: Number, default: null }
})

const emit = defineEmits(['update:modelValue', 'blur', 'selected'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)

const statusLabels = {
  draft: 'Borrador',
  under_review: 'En evaluación',
  approved: 'Aprobada',
  rejected: 'Rechazada'
}

const fetchApplications = async (query) => {
  if (!query || query.length < 1) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get('/api/rental-applications', {
      params: { 'search[text]': query, with_property: true }
    })

    const results = data.data.map(app => ({
      ...app,
      label: `#${String(app.id).padStart(8, '0')} - ${app.applicant?.last_name} ${app.applicant?.name}`,
      statusLabel: statusLabels[app.status] || app.status
    }))

    items.value = results.length
      ? results
      : [{ id: '__new__', label: `Crear nueva solicitud "${query}"` }]
  } catch (e) {
    console.error('Error buscando solicitudes de alquiler:', e)
  } finally {
    loading.value = false
  }
}

const fetchApplicationById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/rental-applications/${id}`)
    const app = {
      ...data,
      label: `#${String(data.id).padStart(4, '0')} - ${data.applicant?.last_name} ${data.applicant?.name}`,
      statusLabel: statusLabels[data.status] || data.status
    }
    selected.value = app
    items.value = [app]
    emit('selected', app)
  } catch (e) {
    console.error('Error cargando solicitud:', e)
  }
}

watch(() => props.modelValue, async (val) => {
  if (typeof val === 'number') {
    await fetchApplicationById(val)
  } else if (val === null) {
    selected.value = null
    items.value = []
  }
}, { immediate: true })

watch(selected, (val) => {
  if (val?.id === '__new__') {
    dialog.value = true
    selected.value = null
    emit('update:modelValue', null)
  } else {
    emit('update:modelValue', val?.id ?? null)
    if (val) emit('selected', val)
  }
})

watch(search, debounce((val) => {
  fetchApplications(val)
}, 300))

const handleCreateApplication = async (formData) => {
  saving.value = true
  try {
    const { data } = await axios.post('/api/rental-applications', formData)
    const app = {
      ...data,
      label: `#${String(data.id).padStart(4, '0')} - ${data.applicant?.last_name} ${data.applicant?.name}`,
      statusLabel: statusLabels[data.status] || data.status
    }

    selected.value = app
    items.value = [app]
    emit('update:modelValue', app.id)
    emit('selected', app)
    dialog.value = false
  } catch (e) {
    console.error('Error creando solicitud:', e)
  } finally {
    saving.value = false
  }
}
</script>
