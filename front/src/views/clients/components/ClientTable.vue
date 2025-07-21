<template>
  <div class="d-flex align-center mb-5">
    <v-select
      v-model="searchType"
      :items="clientTypes"
      item-title="title"
      item-value="value"
      label="Tipo de Cliente"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 200px"
    ></v-select>
    <v-text-field
      v-model="searchName"
      label="Buscar por nombre, apellido o documento"
      prepend-inner-icon="mdi-magnify"
      variant="solo-filled"
      flat
      hide-details
      single-line
      class="ml-2"
    ></v-text-field>
  </div>

  <div>
    <v-data-table-server
      v-model:items-per-page="options.itemsPerPage"
      v-model:sort-by="options.sortBy"
      v-model:page="options.page"
      :headers="headers"
      :items="clients"
      :items-length="totalClients"
      :loading="loading"
      item-value="id"
      @update:options="fetchClients"
    >
      <template #[`item.name`]="{ item }">
        <RouterLink
          :to="`/clients/${item.id}`"
          class="text-primary font-weight-bold text-decoration-none"
        >
          {{ item.name }} {{ item.last_name }}
        </RouterLink>
        </template>
      <template #[`item.document`]="{ item }">
        <span v-if="item.no_document"></span>
        <span v-else> {{ item.document_type?.name || '—' }} {{ item.document_number || '' }} </span>
      </template>
      <template #[`item.type`]="{ item }">
        {{ getClientTypeName(item.type) }}
      </template>

      <template #[`item.actions`]="{ item }">
        <v-icon class="me-2" size="small" @click="emit('edit-client', item)" title="Editar Cliente">
          mdi-pencil
        </v-icon>
        <v-icon size="small" @click="openDeleteDialog(item)" title="Eliminar Cliente">
          mdi-delete
        </v-icon>
      </template>
    </v-data-table-server>
  </div>

  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que deseas eliminar al cliente
        <strong>{{ clientToDelete?.name }} {{ clientToDelete?.last_name }}</strong
        >? Esta acción no se puede deshacer.
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue-darken-1" variant="text" @click="closeDeleteDialog">Cancelar</v-btn>
        <v-btn color="red-darken-1" variant="text" @click="confirmDelete">Eliminar</v-btn>
        <v-spacer></v-spacer>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, reactive } from 'vue'
import axios from 'axios'
const emit = defineEmits(['edit-client', 'delete-client', 'error'])

// Estado reactivo
const clients = ref([])
const loading = ref(true)
const totalClients = ref(0)
const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }],
})
const searchName = ref('')
const searchType = ref(null)

const dialogDelete = ref(false)
const clientToDelete = ref(null)

// Datos de la tabla
const headers = [
  { title: 'Tipo', key: 'type', sortable: true },
  { title: 'Nombre Completo', key: 'name', sortable: true },
  { title: 'Documento', key: 'document', sortable: false },
  { title: 'Email', key: 'email', sortable: false },
  { title: 'Teléfono', key: 'phone', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const clientTypes = [
  { title: 'Persona', value: 'individual' },
  { title: 'Empresa', value: 'company' },
]

const getClientTypeName = (value) => {
  const match = clientTypes.find((t) => t.value === value)
  return match ? match.title : '—'
}

// Lógica de API
const fetchClients = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'id',
    sort_direction: options.sortBy[0]?.order || 'asc',
    'search[name]': searchName.value,
    'search[type]': searchType.value,
  }
  try {
    const response = await axios.get('/api/clients', { params })
    clients.value = response.data.data
    totalClients.value = response.data.meta.total
  } catch (error) {
    console.error('Error al obtener los clientes:', error)
    emit('error', 'No se pudieron cargar los clientes. Intente nuevamente.')
  } finally {
    loading.value = false
  }
}

// Exponer método para el padre
defineExpose({
  reload: fetchClients,
})

// Lógica del diálogo
const openDeleteDialog = (client) => {
  clientToDelete.value = client
  dialogDelete.value = true
}
const closeDeleteDialog = () => {
  dialogDelete.value = false
  setTimeout(() => {
    clientToDelete.value = null
  }, 300)
}
const confirmDelete = () => {
  if (clientToDelete.value) {
    emit('delete-client', clientToDelete.value)
  }
  closeDeleteDialog()
}

// Watchers para los filtros
let debounceTimer = null
watch(searchName, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    options.page = 1
    fetchClients()
  }, 500)
})
watch(searchType, () => {
  options.page = 1
  fetchClients()
})
</script>
