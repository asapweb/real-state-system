<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Personas Asociadas</span>
      <v-spacer></v-spacer>
      <v-btn
        v-if="editable"
        class="text-none"
        color="primary"
        text="Agregar"
        variant="text"
        slim
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        Agregar
      </v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:sort-by="options.sortBy"
        v-model:page="options.page"
        :headers="headers"
        :items="clients"
        :items-length="total"
        :loading="loading"
        item-value="id"
        @update:options="fetchClients"
      >
        <template #[`item.client.full_name`]="{ item }">
          {{ item.client?.last_name }}, {{ item.client?.name }}
        </template>

        <template #[`item.role`]="{ item }">
          {{ roleLabel(item.role) }}
        </template>

        <template #[`item.ownership_percentage`]="{ item }">
          <span v-if="item.ownership_percentage !== null">{{ item.ownership_percentage }}%</span>
          <span v-else>—</span>
        </template>

        <template #[`item.is_primary`]="{ item }">
          <v-icon color="green" v-if="item.is_primary">mdi-check-circle</v-icon>
          <v-icon color="grey" v-else>mdi-minus-circle</v-icon>
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="editClient(item)">mdi-pencil</v-icon>
          <v-icon size="small" @click="confirmDelete(item)">mdi-delete</v-icon>
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>

  <!-- Diálogo de formulario -->
  <v-dialog v-model="dialog" max-width="600px">
    <ContractClientForm
      :initial-data="selectedClient"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Confirmación de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text>¿Eliminar esta persona asociada del contrato?</v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteClient">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import ContractClientForm from './ContractClientForm.vue'

const props = defineProps({
  contractId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})

const emit = defineEmits(['updated'])

const snackbar = useSnackbar()

const clients = ref([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const dialogDelete = ref(false)
const selectedClient = ref(null)
const clientToDelete = ref(null)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'asc' }],
})

const headers = [
  { title: 'Cliente', key: 'client.full_name', sortable: false },
  { title: 'Rol', key: 'role', sortable: true },
  { title: '% Titularidad', key: 'ownership_percentage', sortable: true },
  { title: 'Responsable principal', key: 'is_primary', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const roleLabel = (role) => {
  const map = {
    owner: 'Propietario',
    tenant: 'Inquilino',
    guarantor: 'Garante',
  }
  return map[role] || role
}

const fetchClients = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contracts/${props.contractId}/clients`, {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key || 'id',
        sort_direction: options.sortBy[0]?.order || 'asc',
      },
    })
    clients.value = data.data
    total.value = data.total
  } catch (error) {
    console.log(error)
    snackbar.error('No se pudieron cargar los clientes del contrato.')
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  selectedClient.value = null
  dialog.value = true
}

const editClient = (item) => {
  selectedClient.value = item
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedClient.value = null
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (selectedClient.value?.id) {
      await axios.put(
        `/api/contracts/${props.contractId}/clients/${selectedClient.value.id}`,
        formData,
      )
    } else {
      await axios.post(`/api/contracts/${props.contractId}/clients`, formData)
    }
    dialog.value = false
    fetchClients()
    emit('updated')
    snackbar.success('Guardado con éxito')
  } catch (e) {
    console.error(e)
    snackbar.error('Error al guardar')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  clientToDelete.value = item
  dialogDelete.value = true
}

const deleteClient = async () => {
  try {
    await axios.delete(`/api/contracts/${props.contractId}/clients/${clientToDelete.value.id}`)
    snackbar.success('Eliminado con éxito')
    fetchClients()
    emit('updated')
  } catch (error) {
    console.log(error)
    snackbar.error('Error al eliminar')
  } finally {
    dialogDelete.value = false
    clientToDelete.value = null
  }
}

watch(() => props.contractId, fetchClients, { immediate: true })
</script>
