<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useReferenceDataStore } from '../../stores/referenceData'

const props = defineProps({
  modelValue: {
    type: [Number, String, null],
    default: null,
  },
  cityId: {
    type: [Number, String, null],
    default: null,
  },
  label: {
    type: String,
    default: 'Barrio',
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
const selectedNeighborhood = ref(null)
const loading = ref(false)
const error = ref(null)

const neighborhoods = computed(() => {
  const data = referenceDataStore.neighborhoods[props.cityId] || []
  console.log('Neighborhoods data for city', props.cityId, ':', data)
  return data
})

onMounted(async () => {
  if (props.cityId) {
    loading.value = true
    error.value = null
    try {
      await referenceDataStore.fetchNeighborhoods(props.cityId)
      if (
        !selectedNeighborhood.value &&
        referenceDataStore.defaultNeighborhood?.cityId === props.cityId
      ) {
        selectedNeighborhood.value = referenceDataStore.defaultNeighborhood.id
        emit('update:modelValue', selectedNeighborhood.value)
      }
    } catch (err) {
      error.value = 'Error al cargar barrios'
      console.error('Failed to fetch neighborhodds:', err)
    } finally {
      loading.value = false
    }
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
      selectedNeighborhood.value = newValue
    } else {
      console.warn('Invalid modelValue received:', newValue)
    }
  },
  { immediate: true },
)

watch(
  () => props.cityId,
  async (newCityId) => {
    if (!newCityId) {
      selectedNeighborhood.value = null
      emit('update:modelValue', null)
      return
    }
    loading.value = true
    error.value = null
    try {
      await referenceDataStore.fetchNeighborhoods(newCityId)
      const neighborhoods = referenceDataStore.getNeighborhoodsByCityId(newCityId)
      // Si el neighborhood_id actual no existe en los nuevos neighborhoods, seleccionar default si existe
      const valid = neighborhoods.some((n) => n.id === selectedNeighborhood.value)
      if (!valid) {
        const defaultNeighborhood = neighborhoods.find((n) => n.default)
        if (defaultNeighborhood) {
          selectedNeighborhood.value = defaultNeighborhood.id
          emit('update:modelValue', defaultNeighborhood.id)
        } else {
          selectedNeighborhood.value = null
          emit('update:modelValue', null)
        }
      }
    } catch (err) {
      error.value = 'Error al cargar barrios'
      console.error('Failed to fetch neighborhoods:', err)
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

// Cuando cambian los barrios y ya hay un valor, forzar el valor seleccionado
watch(neighborhoods, (newItems) => {
  if (newItems.length && props.modelValue && !selectedNeighborhood.value) {
    const found = newItems.find((n) => n.id === props.modelValue)
    if (found) {
      selectedNeighborhood.value = props.modelValue
    }
  }
})
</script>

<template>
  <v-select
    v-model="selectedNeighborhood"
    :items="neighborhoods"
    item-value="id"
    item-title="name"
    :label="label"
    :clearable="clearable"
    :disabled="loading || disabled || !props.cityId"
    placeholder="Selecciona una ciudad"
    :rules="rules"
    :required="required"
    @update:modelValue="emit('update:modelValue', $event)"
  ></v-select>
  <v-progress-circular v-if="loading" indeterminate size="24" />
  <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
