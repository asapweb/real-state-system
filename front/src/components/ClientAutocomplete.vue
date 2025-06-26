<template>
  <v-autocomplete
    v-model="selectedClient"
    :items="clients"
    :item-title="getClientName"
    item-value="id"
    :label="label"
    :loading="isLoading"
    @update:search="handleSearch"
    @update:model-value="handleSelect"
    ><template v-slot:no-data>
      <div class="pa-2">
        <p>No se encontraron clientes.</p>
        <v-btn color="primary" @click="openClientFormDialog"> Crear nuevo cliente </v-btn>
      </div>
    </template></v-autocomplete
  >

  <!-- Diálogo para crear un nuevo cliente -->
  <v-dialog v-model="isClientFormDialogOpen">
    <clientForm @close="closeClientFormDialog" @saved="handleClientCreated" />
  </v-dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import clientForm from '@/components/ClientForm.vue'

const selectedClientData = ref(null)
const isClientFormDialogOpen = ref(false)

// Abrir el diálogo para crear un nuevo cliente
const openClientFormDialog = () => {
  isClientFormDialogOpen.value = true
}

// Cerrar el diálogo
const closeClientFormDialog = () => {
  isClientFormDialogOpen.value = false
}

// Props: El componente padre puede pasar un valor inicial para `client_id`
const props = defineProps({
  modelValue: {
    default: null,
  },
  label: {
    default: 'Cliente',
  },
})

// Emits: Emitimos el valor seleccionado al componente padre
const emit = defineEmits(['update:model-value'])

const clients = ref([])
const selectedClient = ref(props.modelValue) // Valor inicial desde el padre
const isLoading = ref(false)
const isSelecting = ref(false) // Bandera para evitar búsquedas al seleccionar

// Función para formatear el nombre del cliente
const getClientName = (client) => {
  if (client) {
    console.log('hay  client')
    console.log(client.client_type)
    if (client.client_type === 'person' && client.name && client.last_name) {
      return `${client.name} ${client.last_name}`
    } else if (client.client_type === 'company' && client.company_name) {
      return client.company_name
    }
  }
  return 'Cliente desconocido'
}

// Función para buscar clientes
const searchClients = async (query) => {
  if (query && !isSelecting.value) {
    isLoading.value = true
    try {
      const response = await axios.get('/api/clients', {
        params: { search: { name: query } },
      })
      clients.value = response.data.data
      if (selectedClient.value && selectedClientData.value) {
        const selectedClientInList = clients.value.find(
          (client) => client.id === selectedClient.value,
        )
        if (!selectedClientInList) {
          clients.value.unshift(selectedClientData.value)
        }
      }
    } catch (error) {
      console.error('Error al buscar clientes:', error)
    } finally {
      isLoading.value = false
    }
  }
}

// Debounce para optimizar las búsquedas
const debouncedSearchClients = debounce((query) => {
  searchClients(query)
}, 300)

// Manejar la búsqueda
const handleSearch = (query) => {
  if (!isSelecting.value) {
    debouncedSearchClients(query)
  }
}

// Manejar la selección de un cliente
const handleSelect = (value) => {
  isSelecting.value = true // Activar la bandera al seleccionar un item
  // Buscar el cliente seleccionado en la lista de clientes
  const selectedClientFromList = clients.value.find((client) => client.id === value)
  if (selectedClientFromList) {
    selectedClientData.value = selectedClientFromList // Actualizar los datos del cliente seleccionado
  }
  emit('update:model-value', value) // Emitir el valor seleccionado al padre
  setTimeout(() => {
    isSelecting.value = false // Desactivar la bandera después de un breve retraso
  }, 100)
}

// Manejar la creación de un nuevo cliente
const handleClientCreated = async (newClient) => {
  await loadSelectedClient(newClient)
  closeClientFormDialog()
  emit('update:model-value', newClient)
}

// Función para cargar los datos del cliente seleccionado
const loadSelectedClient = async (clientId) => {
  if (clientId) {
    isLoading.value = true
    try {
      const response = await axios.get(`/api/clients/${clientId}`)
      selectedClientData.value = response.data
      clients.value = [response.data] // Agregar el cliente a la lista de opciones
      selectedClient.value = clientId
    } catch (error) {
      console.error('Error al cargar el cliente:', error)
    } finally {
      isLoading.value = false
    }
  }
}
// Si el valor inicial cambia desde el padre, cargamos el cliente seleccionado
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && selectedClient.value !== newValue) {
      loadSelectedClient(newValue)
    }
  },
)
// Cargar el cliente seleccionado cuando el componente se monta
onMounted(() => {
  if (props.modelValue) {
    loadSelectedClient(props.modelValue)
  }
})
</script>
