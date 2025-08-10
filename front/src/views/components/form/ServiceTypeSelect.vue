<template>
  <v-select
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :items="options"
    item-title="name"
    item-value="id"
    label="Tipo de servicio"
    :error-messages="errorMessages"
    clearable
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: [String, Number], // ✅ Ahora puede ser numérico (ID)
  errorMessages: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const options = ref([])

const fetchServiceTypes = async () => {
  try {
    const { data } = await axios.get('/api/service-types')
    options.value = data.data // ✅ El endpoint devuelve colección en formato resource
  } catch (e) {
    console.error('Error cargando tipos de servicio:', e)
  }
}

onMounted(fetchServiceTypes)
</script>
