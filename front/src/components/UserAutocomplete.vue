<template>
  <v-autocomplete
    v-model="selectedUser"
    :items="users"
    item-title="name"
    item-value="id"
    :label="props.label"
    :loading="isLoading"
    @update:search="handleSearch"
    @update:model-value="handleSelect"
  ></v-autocomplete>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

// Props: El componente padre puede pasar un valor inicial para `user_id`
const props = defineProps({
  modelValue: {
    type: Number,
    default: null,
  },
  label: {
    default: 'User',
  },
})

// Emits: Emitimos el valor seleccionado al componente padre
const emit = defineEmits(['update:model-value'])

const users = ref([])
const selectedUser = ref(props.modelValue) // Valor inicial desde el padre
const isLoading = ref(false)
const isSelecting = ref(false) // Bandera para evitar búsquedas al seleccionar

// Función para buscar useres
const searchUsers = async (query) => {
  if (query && !isSelecting.value) {
    isLoading.value = true
    try {
      const response = await axios.get('/api/users', {
        params: { search: { name: query } },
      })
      users.value = response.data.data
    } catch (error) {
      console.error('Error al buscar usuarios:', error)
    } finally {
      isLoading.value = false
    }
  }
}

// Debounce para optimizar las búsquedas
const debouncedSearchUsers = debounce((query) => {
  searchUsers(query)
}, 300)

// Manejar la búsqueda
const handleSearch = (query) => {
  if (!isSelecting.value) {
    debouncedSearchUsers(query)
  }
}

// Manejar la selección de un usuario
const handleSelect = (value) => {
  isSelecting.value = true // Activar la bandera al seleccionar un item
  emit('update:model-value', value) // Emitir el valor seleccionado al padre
  setTimeout(() => {
    isSelecting.value = false // Desactivar la bandera después de un breve retraso
  }, 100)
}

// Función para cargar los datos del usuario seleccionado
const loadSelectedUser = async (userId) => {
  if (userId) {
    isLoading.value = true
    try {
      const response = await axios.get(`/api/users/${userId}`)
      users.value = [response.data.data] // Agregar el usuario a la lista de opciones
    } catch (error) {
      console.error('Error al cargar el usuario:', error)
    } finally {
      isLoading.value = false
    }
  }
}

// Si el valor inicial cambia desde el padre, cargamos el usuario seleccionado
watch(
  () => props.modelValue,
  (newValue) => {
    console.log('watcher user =========')
    console.log(selectedUser.value)
    console.log(newValue)
    if (selectedUser.value !== newValue) {
      selectedUser.value = newValue
      loadSelectedUser(newValue)
    }
  },
)

// Cargar el usuario seleccionado cuando el componente se monta
onMounted(() => {
  if (props.modelValue) {
    loadSelectedUser(props.modelValue)
  }
})
</script>
