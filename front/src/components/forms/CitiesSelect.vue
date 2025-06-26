<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useReferenceDataStore } from '../../stores/referenceData'

const props = defineProps({
  modelValue: {
    type: [Number, String, null],
    default: null,
  },
  stateId: {
    type: [Number, String, null],
    default: null,
  },
  label: {
    type: String,
    default: 'Ciudad',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  clearable: {
    type: Boolean,
    default: false,
  },
  rules: {
    type: Array,
    default: () => [],
  },
  required: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])
const referenceDataStore = useReferenceDataStore()
const selectedCity = ref(null)
const loading = ref(false)
const error = ref(null)

const cities = computed(() => {
  const data = referenceDataStore.cities[props.stateId] || []
  return data
})

onMounted(async () => {
  if (props.stateId) {
    loading.value = true
    error.value = null
    try {
      await referenceDataStore.fetchCities(props.stateId)
      const citiesList = referenceDataStore.getCitiesByStateId(props.stateId)
      if (props.modelValue && citiesList.some((c) => c.id === props.modelValue)) {
        selectedCity.value = props.modelValue
      } else if (citiesList.length) {
        const defaultCity = citiesList.find((c) => c.default)
        if (defaultCity) {
          selectedCity.value = defaultCity.id
          emit('update:modelValue', defaultCity.id)
        }
      }
    } catch (err) {
      error.value = 'Error al cargar ciudades'
      console.error('Failed to fetch cities:', err)
    } finally {
      loading.value = false
    }
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
      selectedCity.value = newValue
    } else {
      console.warn('Invalid modelValue received:', newValue)
    }
  },
  { immediate: true },
)

watch(
  () => props.stateId,
  async (newStateId) => {
    if (!newStateId) {
      selectedCity.value = null
      emit('update:modelValue', null)
      return
    }
    loading.value = true
    error.value = null
    try {
      await referenceDataStore.fetchCities(newStateId)
      const cities = referenceDataStore.getCitiesByStateId(newStateId)
      // Si el city_id del modelo es válido, seleccionarlo
      if (props.modelValue && cities.some((c) => c.id === props.modelValue)) {
        selectedCity.value = props.modelValue
        emit('update:modelValue', props.modelValue)
      } else {
        // Si el city_id actual no existe en las nuevas cities, seleccionar default si existe
        const defaultCity = cities.find((c) => c.default)
        if (defaultCity) {
          selectedCity.value = defaultCity.id
          emit('update:modelValue', defaultCity.id)
        } else {
          selectedCity.value = null
          emit('update:modelValue', null)
        }
      }
    } catch (err) {
      error.value = 'Error al cargar ciudades'
      console.error('Failed to fetch cities:', err)
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

watch(cities, (newItems) => {
  if (newItems.length && props.modelValue && !selectedCity.value) {
    const found = newItems.find((c) => c.id === props.modelValue)
    if (found) {
      selectedCity.value = props.modelValue
    }
  }
})
</script>

<template>
  <v-select
    v-model="selectedCity"
    :items="cities"
    item-value="id"
    item-title="name"
    :label="label"
    :clearable="clearable"
    :disabled="loading || disabled || !props.stateId"
    placeholder="Selecciona una ciudad"
    :rules="rules"
    :required="required"
    @update:modelValue="emit('update:modelValue', $event)"
  >
  </v-select>
  <v-progress-circular v-if="loading" indeterminate size="24" />
  <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
