<template>
  <div>
    <div class="d-flex align-center mb-5 gap-2">
      <v-text-field
        v-model="searchText"
        label="Buscar por dirección, matrícula o matrícula"
        prepend-inner-icon="mdi-magnify"
        variant="solo-filled"
        flat
        hide-details
        single-line
        style="flex: 1;"
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
        style="max-width: 200px;"
      />
    </div>

    <v-data-table-server
      v-model:items-per-page="options.itemsPerPage"
      v-model:sort-by="options.sortBy"
      v-model:page="options.page"
      :headers="headers"
      :items="contracts"
      :items-length="total"
      :loading="loading"
      item-value="id"
      @update:options="fetchContracts"
    >
      <template #item.id="{ item }">
        <RouterLink :to="`/contracts/${item.id}`" class="text-primary text-decoration-none font-weight-bold">
          {{ formatModelId(item.id, 'CON') }}
        </RouterLink>
      </template>

      <template #item.property.street="{ item }">
        <RouterLink :to="`/properties/${item.property?.id}`" class="text-primary text-decoration-none font-weight-bold">
          <span v-if="item.property?.neighborhood?.name">{{ item.property?.neighborhood?.name }}, </span>
          {{ item.property?.street }} {{ item.property?.number || '' }}
        </RouterLink>
      </template>

      <template #item.start_date="{ item }">
        {{ formatDate(item.start_date) }}
      </template>

      <template #item.end_date="{ item }">
        {{ formatDate(item.end_date) }}
      </template>

      <template #item.monthly_amount="{ item }">
        {{ formatMoney(item.monthly_amount, item.currency) }}
      </template>

      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="flat">
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <v-icon size="small" class="me-2" @click="emit('edit', item)">mdi-pencil</v-icon>
        <v-icon size="small" @click="confirmDelete(item)">mdi-delete</v-icon>
      </template>
    </v-data-table-server>

    <!-- Confirmación -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Confirmar eliminación</v-card-title>
        <v-card-text>
          ¿Estás seguro de eliminar el contrato
          <strong>{{ formatModelId(contractToDelete?.id, 'CTR') }}</strong>?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="red" variant="flat" @click="confirmDeleteAction">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import { formatModelId } from '@/utils/models-formatter'

const emit = defineEmits(['edit', 'delete', 'error'])

const contracts = ref([])
const total = ref(0)
const loading = ref(false)

const searchText = ref('')
const searchStatus = ref(null)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'desc' }]
})

const headers = [
  { title: '#', key: 'id', sortable: true },
  { title: 'Propiedad', key: 'property.street', sortable: false },
  { title: 'Inicio', key: 'start_date', sortable: true },
  { title: 'Fin', key: 'end_date', sortable: true },
  { title: 'Monto mensual', key: 'monthly_amount', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' }
]

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'Activo', value: 'active' },
  { label: 'Finalizado', value: 'finished' },
  { label: 'Cancelado', value: 'cancelled' }
]

const statusLabel = (status) => statusOptions.find(s => s.value === status)?.label || '—'

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'grey'
    case 'active': return 'green'
    case 'finished': return 'blue-grey'
    case 'cancelled': return 'red'
    default: return 'default'
  }
}

const fetchContracts = async () => {
  loading.value = true
  const params = {
    page: options.page,
    per_page: options.itemsPerPage,
    sort_by: options.sortBy[0]?.key || 'id',
    sort_direction: options.sortBy[0]?.order || 'desc',
    'search[status]': searchStatus.value,
    'search[text]': searchText.value,
    with_property: true
  }

  try {
    const { data } = await axios.get('/api/contracts', { params })
    contracts.value = data.data
    total.value = data.total
  } catch (error) {
    emit('error', 'No se pudieron cargar los contratos.')
    console.error(error)
  } finally {
    loading.value = false
  }
}

defineExpose({ reload: fetchContracts })

const dialogDelete = ref(false)
const contractToDelete = ref(null)

const confirmDelete = (item) => {
  contractToDelete.value = item
  dialogDelete.value = true
}

const confirmDeleteAction = () => {
  emit('delete', contractToDelete.value)
  dialogDelete.value = false
}
watch(searchText, () => {
  clearTimeout(this.debounceTimer)
  this.debounceTimer = setTimeout(() => {
    options.page = 1
    fetchContracts()
  }, 500)
})

watch(searchStatus, () => {
  options.page = 1
  fetchContracts()
})

onMounted(fetchContracts)
</script>
