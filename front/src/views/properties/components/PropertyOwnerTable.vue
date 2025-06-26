<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Propietarios</span>
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
        :items="owners"
        :items-length="total"
        :loading="loading"
        @update:options="fetchOwners"
      >
        <template #[`item.client`]="{ item }">
          {{ item.client?.name }} {{ item.client?.last_name }}
        </template>
        <template #[`item.ownership_percentage`]="{ item }">
          {{ item.ownership_percentage }}%
        </template>
        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="editOwner(item)" title="Editar"
            >mdi-pencil</v-icon
          >
          <v-icon size="small" @click="confirmDelete(item)" title="Eliminar">mdi-delete</v-icon>
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>
  <v-dialog v-model="dialog" max-width="600px">
    <PropertyOwnerForm
      :loading="creating"
      :initial-data="selectedOwner"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <ConfirmDialog
    v-model="deleteDialog"
    :message="`¿Estás seguro de que querés eliminar al propietario <strong>${ownerToDelete?.client?.name} ${ownerToDelete?.client?.last_name}</strong>?`"
    title="Confirmar eliminación"
    confirm-text="Eliminar"
    confirm-color="error"
    :loading="deleting"
    @confirm="deleteOwner"
    @cancel="deleteDialog = false"
  />
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import PropertyOwnerForm from './PropertyOwnerForm.vue'
import ConfirmDialog from '@/views/components/ConfirmDialog.vue'

const props = defineProps({
  propertyId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})
const emit = defineEmits(['updated'])

const owners = ref([])
const total = ref(0)
const loading = ref(false)
const creating = ref(false)
const dialog = ref(false)
const selectedOwner = ref(null)

const snackbar = useSnackbar()

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }],
})

const headers = [
  { title: 'Cliente', key: 'client', sortable: false },
  { title: 'Porcentaje', key: 'ownership_percentage', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const deleting = ref(false)

const deleteDialog = ref(false)
const ownerToDelete = ref(null)

const confirmDelete = (item) => {
  ownerToDelete.value = item
  deleteDialog.value = true
}

const deleteOwner = async () => {
  if (!ownerToDelete.value) return

  deleting.value = true
  try {
    await axios.delete(`/api/properties/${props.propertyId}/owners/${ownerToDelete.value.id}`)
    snackbar.success('Propietario eliminado con éxito.')
    fetchOwners()
    emit('updated')
    deleteDialog.value = false
    ownerToDelete.value = null
  } catch (error) {
    console.error('Error al eliminar propietario:', error)
    snackbar.error('Error al eliminar el propietario.')
  } finally {
    deleting.value = false
  }
}

const fetchOwners = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/properties/${props.propertyId}/owners`, {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key || 'id',
        sort_direction: options.sortBy[0]?.order || 'asc',
      },
    })
    owners.value = data.data
    total.value = data.total
  } catch (error) {
    console.error('Error cargando propietarios:', error)
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  selectedOwner.value = null
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedOwner.value = null
}

const editOwner = (owner) => {
  selectedOwner.value = {
    id: owner.id,
    ownership_percentage: owner.ownership_percentage,
    client_id: owner.client?.id || null,
  }
  dialog.value = true
}

const handleSave = async (formData) => {
  creating.value = true
  try {
    if (selectedOwner.value && selectedOwner.value.id) {
      await axios.put(
        `/api/properties/${props.propertyId}/owners/${selectedOwner.value.id}`,
        formData,
      )
    } else {
      await axios.post(`/api/properties/${props.propertyId}/owners`, formData)
    }
    dialog.value = false
    fetchOwners()
    emit('updated')
  } catch (error) {
    let message = 'Error al guardar el propietario'
    if (error.response?.status === 422 && error.response.data?.message) {
      message = error.response.data.message
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    } else if (typeof error.response?.data === 'string') {
      message = error.response.data
    }
    snackbar.error(message)
    console.error(error)
  } finally {
    creating.value = false
  }
}

watch(() => props.propertyId, fetchOwners, { immediate: true })
</script>
