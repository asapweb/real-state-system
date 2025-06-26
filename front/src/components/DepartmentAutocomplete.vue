<template>
  <v-select
    v-model="selectedDepartment"
    :items="departments"
    item-title="name"
    item-value="id"
    :label="label"
    :loading="isLoading"
    :disabled="isLoading"
    :multiple="multiple"
    @update:model-value="handleSelect"
    clearable
  ></v-select>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, watch, onMounted } from 'vue'

// Propiedades
const props = defineProps({
  department_id: {
    type: [String, Object, Number],
    default: null,
  },

  multiple: {
    default: false,
  },
  label: {
    type: [String],
    default: 'Buscar Departamento',
  },
})

// Emits: Emitimos el valor seleccionado al componente padre
const emit = defineEmits(['update:model-value'])

const departments = ref([])
const selectedDepartment = ref(null)
const isLoading = ref(false)

const loadDepartments = async (query) => {
  isLoading.value = true
  await axios
    .get('/api/departments', {
      params: {
        search: { name: query },
      },
    })
    .then((response) => {
      departments.value = response.data.data
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      isLoading.value = false
    })
}
// Manejar la selección de un departamento
const handleSelect = (value) => {
  emit('update:model-value', value) // Emitir el valor seleccionado al padre
}

// Si el valor inicial cambia desde el padre, actualizamos el valor seleccionado
watch(
  () => props.modelValue,
  (newValue) => {
    selectedDepartment.value = newValue
  },
)

onMounted(() => {
  loadDepartments()
})
</script>
