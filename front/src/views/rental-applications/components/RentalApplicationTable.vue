<template>
  <div class="d-flex align-center mb-5 gap-2" v-if="props.showFilters">
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
      style="max-width: 200px;"
    />

    <v-text-field
      v-model="searchText"
      label="Buscar por dirección, matrícula o partida"
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
    :items="applications"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchApplications"
  >
    <template #item.id="{ item }">
      <RouterLink :to="`/rental-applications/${item.id}`" class="text-primary font-weight-bold text-decoration-none">
        SOL-{{ String(item.id).padStart(8, '0') }}
      </RouterLink>
    </template>

    <template #item.created_at="{ item }">
      {{ formatDateTime(item.created_at) }}
    </template>
    <template #item.property.street="{ item }">
      <RouterLink :to="`/properties/${item.property?.id}`" class="text-primary text-decoration-none font-weight-bold">
        <span v-if="item.property?.neighborhood?.name">{{ item.property?.neighborhood?.name }}, </span>
        {{ item.property?.street }} {{ item.property?.number || '' }}
      </RouterLink>
    </template>

    <template #item.rental_offer.id="{ item }">
      <RouterLink v-if="item.rental_offer?.id" :to="`/rental-offers/${item.rental_offer?.id}`" class="text-primary font-weight-bold text-decoration-none">
        OFE-{{ String(item.rental_offer?.id).padStart(8, '0') }}
      </RouterLink>
    </template>

    <template #item.applicant.name="{ item }">
      {{ item.applicant?.last_name }} {{ item.applicant?.name }}
    </template>

    <template #item.status="{ item }">
      <v-chip :color="statusColor(item.status)" size="small" variant="flat">
        {{ statusLabel(item.status) }}
      </v-chip>
    </template>

    <template #item.actions="{ item }">
      <v-icon class="me-2" size="small" @click="emit('edit-application', item)" title="Editar Solicitud">
        mdi-pencil
      </v-icon>
      <v-icon size="small" @click="openDeleteDialog(item)" title="Eliminar Solicitud">
        mdi-delete
      </v-icon>
    </template>
  </v-data-table-server>

  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que deseas eliminar la solicitud para la propiedad
        <strong>{{ applicationToDelete?.property?.street }} {{ applicationToDelete?.property?.number }}</strong>?
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
import axios from '@/services/axios'
import { formatDateTime } from '@/utils/date-formatter';

const emit = defineEmits(['edit-application', 'delete-application', 'error'])

const props = defineProps({
  rentalOfferId: { type: Number, default: null },
  showFilters: { type: Boolean, default: true }
})


const applications = ref([])
const total = ref(0)
const loading = ref(false)

const searchText = ref('')
const searchStatus = ref(null)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }]
})

const headers = [
  { title: 'Solicitud', key: 'id', sortable: true },
  { title: 'Solicitante', key: 'applicant.name', sortable: false },
  { title: 'Propiedad', key: 'property.street', sortable: false },
  { title: 'Oferta', key: 'rental_offer.id', sortable: true },
  { title: 'Creación', key: 'created_at', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' }
]

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'En evaluación', value: 'under_review' },
  { label: 'Aprobado', value: 'approved' },
  { label: 'Rechazado', value: 'rejected' }
]

const statusLabel = (status) => {
  return statusOptions.find(s => s.value === status)?.label || '—'
}

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'grey'
    case 'under_review': return 'blue'
    case 'approved': return 'green'
    case 'rejected': return 'red'
    default: return 'default'
  }
}

const fetchApplications = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'id',
    sort_direction: options.sortBy[0]?.order || 'asc',
    'search[status]': searchStatus.value,
    'search[text]': searchText.value,
    'search[rental_offer_id]': props.rentalOfferId
  }

  try {
    const { data } = await axios.get('/api/rental-applications', { params })
    applications.value = data.data
    total.value = data.total
  } catch (error) {
    emit('error', 'No se pudieron cargar las solicitudes.')
    console.error('Error al obtener solicitudes:', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchApplications)
defineExpose({ reload: fetchApplications })

// Dialogo de confirmación
const dialogDelete = ref(false)
const applicationToDelete = ref(null)

const openDeleteDialog = (application) => {
  applicationToDelete.value = application
  dialogDelete.value = true
}

const closeDeleteDialog = () => {
  dialogDelete.value = false
  applicationToDelete.value = null
}

const confirmDelete = () => {
  emit('delete-application', applicationToDelete.value)
  closeDeleteDialog()
}

// Filtros con debounce
let debounceTimer = null
watch(searchText, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    options.page = 1
    fetchApplications()
  }, 500)
})

watch(searchStatus, () => {
  options.page = 1
  fetchApplications()
})
</script>
