<template>
  <v-card class="mb-4">
    <v-card-title class="d-flex align-center">
      <span>Ajustes del Contrato</span>
      <v-spacer />
      <v-btn
        v-if="editable"
        color="primary"
        variant="text"
        prepend-icon="mdi-plus"
        @click="openDialog(null)"
      >
        Agregar Ajuste
      </v-btn>
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-data-table-server
        v-model:items-per-page="options.itemsPerPage"
        v-model:sort-by="options.sortBy"
        v-model:page="options.page"
        :headers="headers"
        :items="adjustments"
        :items-length="total"
        :loading="loading"
        item-value="id"
        @update:options="fetchAdjustments"
      >
        <template #[`item.effective_date`]="{ item }">
          {{ formatDate(item.effective_date) }}
        </template>

        <template #[`item.type`]="{ item }">
          {{ adjustmentLabel(item.type) }}
        </template>

        <template #[`item.value`]="{ item }">
          <template v-if="item.type === 'percentage'">
            {{ item.value !== null ? `${item.value}%` : '—' }}
          </template>

          <template v-else-if="item.type === 'fixed' || item.type === 'negotiated'">
            {{ item.value !== null ? formatMoney(item.value) : '—' }}
          </template>

          <template v-else-if="item.type === 'index'">
            <span>
              {{ item.index_type?.name ?? 'Índice' }}
              <span v-if="item.value !== null"> · {{ item.value }}%</span>
            </span>
          </template>

          <template v-else> — </template>
        </template>

        <template #[`item.actions`]="{ item }">
          <v-icon class="me-2" size="small" @click="openDialog(item)" title="Editar ajuste"
            >mdi-pencil</v-icon
          >
          <v-icon size="small" @click="confirmDelete(item)" title="Eliminar ajuste"
            >mdi-delete</v-icon
          >
        </template>
      </v-data-table-server>
    </v-card-text>
  </v-card>

  <!-- Formulario embebido -->
  <v-dialog v-model="dialog" max-width="600px">
    <ContractAdjustmentForm
      :initial-data="selectedAdjustment"
      :loading="saving"
      @submit="handleSave"
      @cancel="closeDialog"
    />
  </v-dialog>

  <!-- Confirmación de eliminación -->
  <v-dialog v-model="dialogDelete" max-width="500px">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text> ¿Estás seguro de que querés eliminar este ajuste? </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialogDelete = false">Cancelar</v-btn>
        <v-btn color="error" @click="deleteAdjustment">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import ContractAdjustmentForm from './ContractAdjustmentForm.vue'
import { useSnackbar } from '@/composables/useSnackbar'

const props = defineProps({
  contractId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})
const emit = defineEmits(['updated'])
const snackbar = useSnackbar()

const dialog = ref(false)
const dialogDelete = ref(false)
const selectedAdjustment = ref(null)
const adjustmentToDelete = ref(null)
const adjustments = ref([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'effective_date', order: 'asc' }],
})

const headers = [
  { title: 'Desde', key: 'effective_date', sortable: true },
  { title: 'Tipo', key: 'type', sortable: false },
  { title: 'Valor', key: 'value', sortable: true },
  { title: 'Notas', key: 'notes', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const adjustmentLabel = (type) => {
  const map = {
    fixed: 'Monto fijo',
    percentage: 'Porcentaje',
    index: 'Índice',
    negotiated: 'Negociado',
  }
  return map[type] || type
}

const fetchAdjustments = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/contracts/${props.contractId}/adjustments`, {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
      },
    })
    adjustments.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar ajustes del contrato')
  } finally {
    loading.value = false
  }
}

const openDialog = (item) => {
  selectedAdjustment.value = item
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  selectedAdjustment.value = null
}

const handleSave = async (formData) => {
  saving.value = true
  try {
    if (formData.id) {
      await axios.put(`/api/contracts/${props.contractId}/adjustments/${formData.id}`, formData)
    } else {
      await axios.post(`/api/contracts/${props.contractId}/adjustments`, formData)
    }
    snackbar.success('Ajuste guardado correctamente')
    fetchAdjustments()
    emit('updated')
    dialog.value = false
  } catch (e) {
    console.error(e)
    snackbar.error('Error al guardar ajuste')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  adjustmentToDelete.value = item
  dialogDelete.value = true
}

const deleteAdjustment = async () => {
  try {
    await axios.delete(
      `/api/contracts/${props.contractId}/adjustments/${adjustmentToDelete.value.id}`,
    )
    snackbar.success('Ajuste eliminado')
    fetchAdjustments()
    emit('updated')
  } catch (e) {
    console.error(e)
    snackbar.error('Error al eliminar ajuste')
  } finally {
    dialogDelete.value = false
    adjustmentToDelete.value = null
  }
}

watch(() => props.contractId, fetchAdjustments, { immediate: true })
</script>
