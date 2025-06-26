<template>
  <div>
    <v-autocomplete
      v-model="selected"
      v-model:search="search"
      :items="items"
      item-title="label"
      item-value="id"
      label="Oferta de alquiler"
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
            Crear nueva oferta "{{ search }}"
          </v-list-item-title>
          <template v-else>
            <v-list-item-title>
              {{ item.label }}
            </v-list-item-title>
            <v-list-item-subtitle>
              {{ item.currency }} {{ formatPrice(item.price) }}
            </v-list-item-subtitle>
          </template>
        </v-list-item>
      </template>
    </v-autocomplete>

    <v-dialog v-model="dialog" max-width="900px">
      <v-card title="Crear Oferta de Alquiler">
        <RentalOfferForm
          :initial-data="null"
          :loading="saving"
          @submit="handleCreateOffer"
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
import RentalOfferForm from '@/views/rental-offers/components/RentalOfferForm.vue'

const props = defineProps({
  modelValue: [Number, Object, null],
  errorMessages: Array
})

const emit = defineEmits(['update:modelValue', 'blur', 'selected'])

const selected = ref(null)
const search = ref('')
const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const saving = ref(false)

const fetchOffers = async (query) => {
  if (!query || query.length < 2) {
    items.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get('/api/rental-offers', {
      params: { 'search[text]': query }
    })

    const results = data.data.map(offer => ({
      ...offer,
      label: `${offer.property?.street ?? ''} ${offer.property?.number ?? ''}`.trim(),
    }))

    items.value = results.length
      ? results
      : [{ id: '__new__', label: `Crear nueva oferta "${query}"` }]
  } catch (e) {
    console.error('Error buscando ofertas:', e)
  } finally {
    loading.value = false
  }
}

const fetchOfferById = async (id) => {
  if (!id) return
  try {
    const { data } = await axios.get(`/api/rental-offers/${id}`)
    const offer = {
      ...data,
      label: `${data.property?.street ?? ''} ${data.property?.number ?? ''}`.trim()
    }
    selected.value = offer
    items.value = [offer]
    emit('selected', offer) // ✅ esto es lo nuevo
  } catch (e) {
    console.error('Error cargando oferta:', e)
  }
}


watch(() => props.modelValue, async (val) => {
  if (typeof val === 'number') {
    await fetchOfferById(val)
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
  fetchOffers(val)
}, 300))

const formatPrice = (value) => {
  if (!value) return '—'
  return parseFloat(value).toLocaleString('es-AR', { minimumFractionDigits: 2 })
}

const handleCreateOffer = async (formData) => {
  saving.value = true
  try {
    const { data } = await axios.post('/api/rental-offers', formData)
    const offer = {
      ...data,
      label: `${data.property?.street ?? ''} ${data.property?.number ?? ''}`.trim()
    }

    selected.value = offer
    items.value = [offer]
    emit('update:modelValue', offer.id)
    emit('selected', offer)
    dialog.value = false
  } catch (e) {
    console.error('Error creando oferta:', e)
  } finally {
    saving.value = false
  }
}
</script>
