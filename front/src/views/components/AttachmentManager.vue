<template>
  <div>
    <v-card class="mb-4">
      <v-card-title class="d-flex align-center">
        <span>Archivos</span>
        <v-spacer></v-spacer>
        <div class="d-flex align-center gap-2">
          <!-- Filtrar -->
          <v-btn
            icon
            variant="text"
            size="small"
            class="me-2 pa-0"
            @click="showFilters = !showFilters"
            :color="showFilters ? 'primary' : 'default'"
          >
            <v-icon size="18">mdi-filter-variant</v-icon>
          </v-btn>

          <!-- Editar -->
          <v-btn
            class="text-none"
            color="primary"
            text="Subir archivo"
            variant="text"
            slim
            @click="dialog = true"
          />
        </div>
      </v-card-title>
      <v-divider></v-divider>

      <v-card-text>
        <v-row v-if="showFilters">
          <v-col cols="12" md="4">
            <v-select
              v-model="categoryFilter"
              :items="categories"
              item-title="name"
              item-value="id"
              label="Categoría"
              variant="solo-filled"
              flat
              clearable
              @update:model-value="loadAttachments"
            />
          </v-col>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="search"
              variant="solo-filled"
              flat
              label="Buscar"
              @input="loadAttachments"
              clearable
            />
          </v-col>
        </v-row>

        <v-data-table-server
          v-model:items-per-page="options.itemsPerPage"
          v-model:sort-by="options.sortBy"
          v-model:page="options.page"
          :headers="headers"
          :items="attachments"
          :items-length="total"
          :loading="loading"
          :search="search"
          @update:options="loadAttachments"
        >
          <template #[`item.created_at`]="{ item }">
            {{ formatDateTime(item.created_at) }}
          </template>
          <template #[`item.name`]="{ item }">
            <a :href="item.file_url" target="_blank">{{ item.name }}</a>
          </template>
          <template #[`item.size`]="{ item }">
            {{ formatSize(item.size) }}
          </template>
          <template #[`item.uploaded_by`]="{ item }">
            {{ item.uploaded_by ? item.uploaded_by.name : 'Desconocido' }}
          </template>
          <template #[`item.actions`]="{ item }">
            <v-icon size="small" class="me-2" @click="deleteAttachment(item.id)">mdi-delete</v-icon>
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>Subir archivo</v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="uploadFile">
            <v-select
              v-model="form.attachment_category_id"
              :items="categories"
              item-value="id"
              item-title="name"
              label="Categoría"
              required
            />
            <v-text-field v-model="form.name" label="Nombre (opcional)" />
            <v-file-input v-model="form.file" label="Archivo" required accept="*/*" />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" :loading="uploading" @click="uploadFile">Subir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '@/services/axios'
import { formatDateTime } from '@/utils/date-formatter'
const props = defineProps({
  attachableType: String,
  attachableId: Number,
})
const showFilters = ref(false)

const attachments = ref([])
const total = ref(0)
const categories = ref([])
const categoryFilter = ref(null)
const search = ref('')
const options = reactive({ page: 1, itemsPerPage: 10, sortBy: [], sortDesc: [] })

const loading = ref(false)
const uploading = ref(false)
const dialog = ref(false)
const formRef = ref(null)

const form = ref({
  attachment_category_id: null,
  name: '',
  file: null,
})

const snackbar = ref({ show: false, text: '', color: 'success' })

const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Categoría', key: 'category.name' },
  // { title: 'Tipo', key: 'mime_type' },
  { title: 'Subido por', key: 'uploaded_by' },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Tamaño', key: 'size' },
  { title: '', key: 'actions', sortable: false },
]

const loadCategories = async () => {
  const { data } = await axios.get(`/api/attachment-categories/all?context=${props.attachableType}`)
  categories.value = data
}

const loadAttachments = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(
      `/api/attachments/${props.attachableType}/${props.attachableId}`,
      {
        params: {
          search: search.value,
          category_id: categoryFilter.value,
          sort_by: options.sortBy[0] || 'created_at',
          sort_direction: options.sortDesc[0] ? 'desc' : 'asc',
          page: options.page,
          per_page: options.itemsPerPage,
        },
      },
    )
    attachments.value = data.data
    total.value = data.total
  } catch (error) {
    console.log(error)
    snackbar.value = { show: true, text: 'Error al cargar archivos', color: 'error' }
  } finally {
    loading.value = false
  }
}

const uploadFile = async () => {
  if (!form.value.file || !form.value.attachment_category_id) return

  const formData = new FormData()
  formData.append('file', form.value.file)
  formData.append('attachment_category_id', form.value.attachment_category_id)
  formData.append('name', form.value.name)
  formData.append('attachable_type', props.attachableType)
  formData.append('attachable_id', props.attachableId)

  uploading.value = true

  try {
    await axios.post(`/api/attachments/${props.attachableType}/${props.attachableId}`, formData)
    snackbar.value = { show: true, text: 'Archivo subido correctamente', color: 'success' }
    dialog.value = false
    form.value = { attachment_category_id: null, name: '', file: null }
    await loadAttachments()
  } catch (error) {
    console.log(error)
    snackbar.value = { show: true, text: 'Error al subir archivo', color: 'error' }
  } finally {
    uploading.value = false
  }
}

const deleteAttachment = async (id) => {
  if (!confirm('¿Eliminar este archivo?')) return

  try {
    await axios.delete(`/api/attachments/${id}`)
    snackbar.value = { show: true, text: 'Archivo eliminado', color: 'success' }
    await loadAttachments()
  } catch (error) {
    console.log(error)
    snackbar.value = { show: true, text: 'Error al eliminar archivo', color: 'error' }
  }
}

const formatSize = (bytes) => {
  const kb = bytes / 1024
  return kb > 1024 ? (kb / 1024).toFixed(2) + ' MB' : kb.toFixed(0) + ' KB'
}

onMounted(() => {
  loadCategories()
  loadAttachments()
})
</script>

<style scoped>
.v-data-table .v-icon {
  cursor: pointer;
}
</style>
