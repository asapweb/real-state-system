<template>
  <div class="d-flex align-center mb-5 gap-2">
    <v-select
      v-model="searchType"
      :items="propertyTypes"
      item-title="name"
      item-value="id"
      label="Tipo"
      variant="solo-filled"
      flat
      hide-details
      clearable
      style="max-width: 200px;"
    />

    <v-select
      v-model="searchStatus"
      :items="statusOptions"
      item-title="label"
      item-value="value"
      label="Estado"
      variant="solo-filled"
      flat
      hide-details
      clearable
      class="ml-2"
      style="max-width: 200px;"
    />

    <v-text-field
      v-model="searchText"
      label="Buscar por dirección, partida o matrícula"
      prepend-inner-icon="mdi-magnify"
      variant="solo-filled"
      flat
      class="ml-2"
      hide-details
      single-line
      style="flex: 1;"
    />
  </div>


  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="properties"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchProperties"
  >
    <template #item.property_type.name="{ item }">
      {{ item.property_type?.name || '—' }}
    </template>
    <template #item.created_at="{ item }">
      {{ formatDateTime(item.created_at) }}
    </template>
    <template #item.id="{ item }">
      <RouterLink :to="`/properties/${item.id}`" class="text-primary font-weight-bold text-decoration-none">
        {{ formatModelId(item.id, 'PRO') }}
      </RouterLink>
    </template>
    <template #item.street="{ item }">
      {{ item.street }} {{ item.number || '' }}
    </template>

    <template #item.city.name="{ item }">
      {{ item.city?.name || '—' }}
    </template>

    <template #item.status="{ item }">
      <v-chip :color="statusColor(item.status)" size="small" variant="flat">
        {{ statusLabel(item.status) }}
      </v-chip>
    </template>

    <template #item.actions="{ item }">
      <v-icon class="me-2" size="small" @click="emit('edit-property', item)" title="Editar Propiedad">
        mdi-pencil
      </v-icon>
      <v-icon size="small" @click="openDeleteDialog(item)" title="Eliminar Propiedad">
        mdi-delete
      </v-icon>
    </template>
  </v-data-table-server>

  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que deseas eliminar la propiedad
        <strong>{{ propertyToDelete?.street }} {{ propertyToDelete?.number }}</strong>?
        Esta acción no se puede deshacer.
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="blue-darken-1" variant="text" @click="closeDeleteDialog">Cancelar</v-btn>
        <v-btn color="red-darken-1" variant="text" @click="confirmDelete">Eliminar</v-btn>
        <v-spacer />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, reactive, onMounted } from 'vue'
import axios from 'axios'
import { formatDateTime } from '@/utils/date-formatter';
import { formatModelId } from '@/utils/models-formatter';

const emit = defineEmits(['edit-property', 'delete-property', 'error'])

const properties = ref([])
const total = ref(0)
const loading = ref(false)

const searchText = ref('')
const searchStatus = ref(null)
const searchType = ref(null);
const propertyTypes = ref([]);

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }]
})

const headers = [
  { title: 'Propiedad', key: 'id', sortable: false },
  { title: 'Tipo', key: 'property_type.name', sortable: false },
  { title: 'Dirección', key: 'street', sortable: true },
  { title: 'Ciudad', key: 'city.name', sortable: false },
  { title: 'Barrio', key: 'neighborhood.name', sortable: false },
  { title: 'Creación', key: 'created_at', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' }
]

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'Publicado', value: 'published' },
  { label: 'Archivado', value: 'archived' }
]

const statusLabel = (status) => {
  return statusOptions.find(s => s.value === status)?.label || '—'
}

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'grey';
    case 'published': return 'green';
    case 'archived': return 'red';
    default: return 'default';
  }
}

const fetchProperties = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'id',
    sort_direction: options.sortBy[0]?.order || 'asc',
    'search[status]': searchStatus.value,
    'search[text]': searchText.value,
    'search[property_type_id]': searchType.value,
  }
  try {
    const { data } = await axios.get('/api/properties', { params })
    properties.value = data.data
    total.value = data.total
  } catch (error) {
    emit('error', 'No se pudieron cargar las propiedades.')
    console.error('Error al obtener propiedades:', error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/property-types');
    propertyTypes.value = data;
  } catch (error) {
    console.error('Error al cargar tipos de propiedad:', error);
    emit('error', 'No se pudieron cargar los tipos de propiedad.');
  }
});


defineExpose({ reload: fetchProperties })

// Dialogo de confirmación
const dialogDelete = ref(false)
const propertyToDelete = ref(null)

const openDeleteDialog = (property) => {
  propertyToDelete.value = property
  dialogDelete.value = true
}

const closeDeleteDialog = () => {
  dialogDelete.value = false
  propertyToDelete.value = null
}

const confirmDelete = () => {
  emit('delete-property', propertyToDelete.value)
  closeDeleteDialog()
}

// Filtros con debounce
let debounceTimer = null
watch(searchText, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    options.page = 1
    fetchProperties()
  }, 500)
})

watch(searchStatus, () => {
  options.page = 1
  fetchProperties()
})

watch(searchType, () => {
  options.page = 1;
  fetchProperties();
});

</script>
