<template>
  <div class="d-flex align-center mb-5 gap-2">
    <v-text-field
      v-model="searchText"
      label="Buscar por dirección, matrícula o partida"
      prepend-inner-icon="mdi-magnify"
      variant="solo-filled"
      flat
      class="mr-2"
      hide-details
      single-line
      style="flex: 1"
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
      style="max-width: 200px"
    />
  </div>

  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="offers"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchOffers"
  >
    <template #[`item.id`]="{ item }">
      <RouterLink
        :to="`/rental-offers/${item.id}`"
        class="text-primary text-decoration-none font-weight-bold"
      >
        {{ formatModelId(item.id, 'OFE') }}
      </RouterLink>
    </template>

    <template #[`item.property.street`]="{ item }">
      <RouterLink
        :to="`/properties/${item.property?.id}`"
        class="text-primary text-decoration-none font-weight-bold"
      >
        <span v-if="item.property?.neighborhood?.name"
          >{{ item.property?.neighborhood?.name }},
        </span>
        {{ item.property?.street }} {{ item.property?.number || '' }}
      </RouterLink>
    </template>

    <template #[`item.created_at`]="{ item }">
      {{ formatDateTime(item.created_at) }}
    </template>

    <template #[`item.availability_date`]="{ item }">
      {{ formatDateTime(item.availability_date) }}
    </template>

    <template #[`item.price`]="{ item }">
      {{ formatMoney(item.price, item.currency) }}
    </template>

    <template #[`item.status`]="{ item }">
      <v-chip :color="statusColor(item.status)" size="small" variant="flat">
        {{ statusLabel(item.status) }}
      </v-chip>
    </template>

    <template #[`item.actions`]="{ item }">
      <v-icon class="me-2" size="small" @click="emit('edit', item)" title="Editar Oferta">
        mdi-pencil
      </v-icon>
      <v-icon size="small" @click="openDeleteDialog(item)" title="Eliminar Oferta">
        mdi-delete
      </v-icon>
    </template>
  </v-data-table-server>

  <!-- Diálogo de confirmación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que deseas eliminar la oferta de
        <strong>{{ offerToDelete?.property?.street }} {{ offerToDelete?.property?.number }}</strong
        >? Esta acción no se puede deshacer.
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
import { formatMoney } from '@/utils/money'
import { formatDateTime } from '@/utils/date-formatter'
import { formatModelId } from '@/utils/models-formatter'
const emit = defineEmits(['edit', 'delete', 'error'])

const offers = ref([])
const total = ref(0)
const loading = ref(false)

const searchText = ref('')
const searchStatus = ref(null)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'desc' }],
})

const headers = [
  { title: '#', key: 'id', sortable: true },
  { title: 'Propiedad', key: 'property.street', sortable: false },
  { title: 'Precio', key: 'price', sortable: true },
  { title: 'Disponible', key: 'availability_date', sortable: true },
  { title: 'Creación', key: 'created_at', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'Publicado', value: 'published' },
  { label: 'Pausado', value: 'paused' },
  { label: 'Cerrado', value: 'closed' },
]

const statusLabel = (status) => {
  return statusOptions.find((s) => s.value === status)?.label || '—'
}

const statusColor = (status) => {
  switch (status) {
    case 'draft':
      return 'grey'
    case 'published':
      return 'green'
    case 'paused':
      return 'orange'
    case 'closed':
      return 'red'
    default:
      return 'default'
  }
}

const fetchOffers = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'id',
    sort_direction: options.sortBy[0]?.order || 'desc',
    'search[status]': searchStatus.value,
    'search[text]': searchText.value,
    with_property: true,
  }
  try {
    const { data } = await axios.get('/api/rental-offers', { params })
    offers.value = data.data
    total.value = data.total
  } catch (error) {
    emit('error', 'No se pudieron cargar las ofertas de alquiler.')
    console.error('Error al obtener rental offers:', error)
  } finally {
    loading.value = false
  }
}

defineExpose({ reload: fetchOffers })

// Diálogo de confirmación
const dialogDelete = ref(false)
const offerToDelete = ref(null)

const openDeleteDialog = (offer) => {
  offerToDelete.value = offer
  dialogDelete.value = true
}

const closeDeleteDialog = () => {
  dialogDelete.value = false
  offerToDelete.value = null
}

const confirmDelete = () => {
  emit('delete', offerToDelete.value)
  closeDeleteDialog()
}

// Filtros con debounce
let debounceTimer = null
watch(searchText, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    options.page = 1
    fetchOffers()
  }, 500)
})

watch(searchStatus, () => {
  options.page = 1
  fetchOffers()
})

onMounted(fetchOffers)
</script>
