<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Servicios</span>
      <v-spacer></v-spacer>
      <div class="d-flex align-center gap-2">
        <v-btn
          v-if="editable"
          class="text-none"
          color="primary"
          text="Subir archivo"
          variant="text"
          slim
          @click="openCreateDialog"
          prepend-icon="mdi-plus"
        >
          Agregar
        </v-btn>
      </div>
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text>
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:sort-by="options.sortBy"
        v-model:page="options.page"
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
            {{ item.is_active ? 'Sí' : 'No' }}
          </v-chip>
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="editService(item)" title="Editar"
            >mdi-pencil</v-icon
          >
          <v-icon size="small" @click="confirmDelete(item)" title="Eliminar">mdi-delete</v-icon>
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>
  <v-dialog v-model="dialog" max-width="600px">
    <PropertyServiceForm
      :loading="saving"
      :initial-data="selectedService"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
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
import PropertyServiceForm from './PropertyServiceForm.vue'

const props = defineProps({
  propertyId: { type: Number, required: true },
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
  { title: 'Tipo', key: 'service_type', sortable: false },
  { title: 'Proveedor', key: 'provider_name', sortable: true },
  { title: 'Titular', key: 'owner_name', sortable: true },
  { title: 'Cuenta', key: 'account_number', sortable: false },
  { title: 'Activo', key: 'is_active', sortable: false },
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
    const { data } = await axios.get(`/api/properties/${props.propertyId}/services`, {
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
  selectedService.value = {
    id: item.id,
    service_type: item.service_type,
    account_number: item.account_number,
    provider_name: item.provider_name,
    owner_name: item.owner_name,
    is_active: item.is_active ?? true,
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedService.value = null
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (selectedService.value?.id) {
      await axios.put(
        `/api/properties/${props.propertyId}/services/${selectedService.value.id}`,
        formData,
      )
    } else {
      await axios.post(`/api/properties/${props.propertyId}/services`, formData)
    }
    dialog.value = false
    fetchServices()
    emit('updated')
    snackbar.success('Servicio guardado con éxito.')
  } catch (e) {
    console.error('Error al guardar servicio:', e)
    snackbar.error('Error al guardar el servicio.')
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
    await axios.delete(`/api/properties/${props.propertyId}/services/${serviceToDelete.value.id}`)
    snackbar.success('Servicio eliminado con éxito.')
    fetchServices()
    emit('updated')
  } catch (e) {
    console.error('Error al eliminar servicio:', e)
    snackbar.error('Error al eliminar el servicio.')
  } finally {
    dialogDelete.value = false
    serviceToDelete.value = null
  }
}

watch(() => props.propertyId, fetchServices, { immediate: true })
</script>
