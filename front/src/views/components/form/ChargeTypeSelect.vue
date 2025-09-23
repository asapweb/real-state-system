<template>
  <v-select
    :density="density"
    :variant="variant"
    :hide-details="hideDetails"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :items="options"
    item-title="name"
    item-value="id"
    label="Tipo de cargo"
    :error-messages="errorMessages"
    clearable
    :loading="loading"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: [String, Number],
  errorMessages: { type: Array, default: () => [] },
  density: { type: String, default: 'compact' },
  variant: { type: String, default: 'solo-filled' },
  hideDetails: { type: [String, Boolean], default: 'auto' },
})

const emit = defineEmits(['update:modelValue', 'loaded'])

const options = ref([])
const loading = ref(false)

const fetchChargeTypes = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/charge-types', { params: { all: true, active: true } })
    options.value = data.data
    emit('loaded', options.value)
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error('Error cargando tipos de cargo:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchChargeTypes)
</script>

