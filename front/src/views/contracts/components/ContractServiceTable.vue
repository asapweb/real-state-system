<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Servicios del contrato</span>
      <v-spacer />
      <v-btn
        color="primary"
        variant="text"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
        v-if="editable"
      >
        Agregar
      </v-btn>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text>
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:page="options.page"
        v-model:sort-by="options.sortBy"
        :headers="headers"
        :items="services"
        :items-length="total"
        :loading="loading"
        item-value="id"
        @update:options="fetchServices"
      >
        <template #[`item.service_type`]="{ item }">
          {{ formatServiceType(item.service_type) }}
        </template>

        <template #[`item.is_active`]="{ item }">
          <v-chip :color="item.is_active ? 'green' : 'grey'" size="small" variant="flat">
            {{ item.is_active ? 'Activo' : 'Inactivo' }}
          </v-chip>
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="editService(item)">mdi-pencil</v-icon>
          <v-icon size="small" @click="confirmDelete(item)">mdi-delete</v-icon>
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>

  <!-- Formulario -->
  <v-dialog v-model="dialog" max-width="700px">
    <ContractServiceForm
      :initial-data="selectedService"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Diálogo de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title>Confirmar eliminación</v-card-title>
      <v-card-text>
        ¿Estás seguro de que querés eliminar el servicio
        <strong>{{ formatServiceType(serviceToDelete?.service_type) }}</strong
        >?
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteService">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractServiceForm from './ContractServiceForm.vue'

const props = defineProps({
  contractId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})
const emit = defineEmits(['updated'])

const snackbar = useSnackbar()
const services = ref([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const dialogDelete = ref(false)

const selectedService = ref(null)
const serviceToDelete = ref(null)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }],
})

const headers = [
  { title: 'Tipo', key: 'service_type' },
  { title: 'Proveedor', key: 'provider_name' },
  { title: 'Titular', key: 'owner_name' },
  { title: 'Cuenta', key: 'account_number' },
  { title: 'Activo', key: 'is_active' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const formatServiceType = (type) => {
  const map = {
    electricity: 'Electricidad',
    water: 'Agua',
    gas: 'Gas',
    internet: 'Internet',
    phone: 'Teléfono',
  }
  return map[type] || type
}

const fetchServices = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contracts/${props.contractId}/services`, {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key || 'id',
        sort_direction: options.sortBy[0]?.order || 'asc',
      },
    })
    services.value = data.data
    total.value = data.total
  } catch (e) {
    console.error('Error al cargar servicios', e)
    snackbar.error('No se pudieron cargar los servicios.')
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  selectedService.value = null
  dialog.value = true
}

const editService = (item) => {
  selectedService.value = { ...item }
  dialog.value = true
}

const closeDialog = () => {
  selectedService.value = null
  dialog.value = false
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (selectedService.value?.id) {
      await axios.put(
        `/api/contracts/${props.contractId}/services/${selectedService.value.id}`,
        formData,
      )
    } else {
      await axios.post(`/api/contracts/${props.contractId}/services`, formData)
    }
    fetchServices()
    emit('updated')
    snackbar.success('Servicio guardado con éxito.')
    closeDialog()
  } catch (e) {
    snackbar.error('No se pudo guardar el servicio.')
    console.error(e)
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  serviceToDelete.value = item
  dialogDelete.value = true
}

const deleteService = async () => {
  try {
    await axios.delete(`/api/contracts/${props.contractId}/services/${serviceToDelete.value.id}`)
    snackbar.success('Servicio eliminado.')
    fetchServices()
    emit('updated')
  } catch (e) {
    snackbar.error('No se pudo eliminar el servicio.')
    console.error(e)
  } finally {
    dialogDelete.value = false
    serviceToDelete.value = null
  }
}

watch(() => props.contractId, fetchServices, { immediate: true })
</script>
