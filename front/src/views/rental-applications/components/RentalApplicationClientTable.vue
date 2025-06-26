<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Personas Asociadas</span>
      <v-spacer></v-spacer>
      <div class="d-flex align-center gap-2">
        <v-btn
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
      <v-data-table
        :items="clients"
        :headers="headers"
        :loading="loading"
        item-value="id"
        density="compact"
      >
        <template #[`item.client`]="{ item }">
          {{ item.client?.last_name }} {{ item.client?.name }}
        </template>

        <template #[`item.role`]="{ item }">
          {{ roleLabel(item.role) }}
        </template>

        <template #[`item.income`]="{ item }">
          {{ formatMoney(item.income, item.currency) }}
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon size="small" class="me-2" @click="openEditDialog(item)">mdi-pencil</v-icon>
          <v-icon size="small" @click="confirmDelete(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>

  <div>
    <!-- Diálogo de eliminación -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Confirmar eliminación</v-card-title>
        <v-card-text>
          ¿Estás seguro de que querés eliminar a
          <strong
            >{{ clientToDelete?.client?.last_name }} {{ clientToDelete?.client?.name }}</strong
          >
          de la solicitud?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="red" variant="flat" @click="deleteClient">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Diálogo de formulario -->
    <v-dialog v-model="dialogForm" max-width="800px" persistent>
      <v-card>
        <v-card-title class="text-h6">
          {{ editingClient ? 'Editar Persona Asociada' : 'Agregar Persona Asociada' }}
        </v-card-title>
        <v-card-text>
          <RentalApplicationClientForm
            :initial-data="editingClient"
            :loading="formLoading"
            @submit="handleFormSubmit"
            @cancel="dialogForm = false"
          />
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/services/axios'
import RentalApplicationClientForm from './RentalApplicationClientForm.vue'

const props = defineProps({
  applicationId: { type: Number, required: true },
})

const clients = ref([])
const loading = ref(false)
const formLoading = ref(false)

const headers = [
  { title: 'Nombre', key: 'client', sortable: false },
  { title: 'Rol', key: 'role', sortable: false },
  { title: 'Ingresos', key: 'income', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const roleLabel = (role) => {
  switch (role) {
    case 'applicant':
      return 'Postulante'
    case 'guarantor':
      return 'Garante'
    case 'co-applicant':
      return 'Co-solicitante'
    case 'spouse':
      return 'Cónyuge'
    default:
      return role
  }
}

const formatMoney = (value, currency) => {
  if (!value) return '—'
  const formatted = parseFloat(value).toLocaleString('es-AR', { minimumFractionDigits: 2 })
  return currency === 'USD' ? `U$D ${formatted}` : `AR$ ${formatted}`
}

const fetchClients = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/rental-applications/${props.applicationId}/clients`)
    clients.value = data.data
  } catch (e) {
    console.error('Error al obtener personas asociadas:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchClients)
watch(() => props.applicationId, fetchClients)

defineExpose({ reload: fetchClients })

// Diálogo de formulario
const dialogForm = ref(false)
const editingClient = ref(null)

const openCreateDialog = () => {
  editingClient.value = null
  dialogForm.value = true
}

const openEditDialog = (item) => {
  editingClient.value = { ...item }
  dialogForm.value = true
}

const handleFormSubmit = async (data) => {
  formLoading.value = true
  try {
    if (editingClient.value?.id) {
      await axios.put(
        `/api/rental-applications/${props.applicationId}/clients/${editingClient.value.id}`,
        data,
      )
    } else {
      await axios.post(`/api/rental-applications/${props.applicationId}/clients`, {
        ...data,
        rental_application_id: props.applicationId,
      })
    }
    dialogForm.value = false
    await fetchClients()
  } catch (e) {
    console.error('Error al guardar persona asociada:', e)
  } finally {
    formLoading.value = false
  }
}

// Diálogo de eliminación
const dialogDelete = ref(false)
const clientToDelete = ref(null)

const confirmDelete = (item) => {
  clientToDelete.value = item
  dialogDelete.value = true
}

const deleteClient = async () => {
  try {
    await axios.delete(
      `/api/rental-applications/${props.applicationId}/clients/${clientToDelete.value.id}`,
    )
    dialogDelete.value = false
    await fetchClients()
  } catch (e) {
    console.error('Error al eliminar persona asociada:', e)
  }
}
</script>
