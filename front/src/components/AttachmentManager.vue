<template>
  <div>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ title }}</span>
        <v-btn color="primary" @click="openUploadDialog" size="small">
          <v-icon left>mdi-upload</v-icon>Adjuntar archivo
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedCategory"
              :items="categories"
              item-title="name"
              item-value="id"
              label="Categoría"
              :loading="loadingCategories"
              :disabled="loadingCategories"
              clearable
            />
          </v-col>
        </v-row>
        <v-table v-if="attachments.length">
          <thead>
            <tr>
              <th>Archivo</th>
              <th>Categoría</th>
              <th>Subido por</th>
              <th>Fecha</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in filteredAttachments" :key="file.id">
              <td>{{ file.category?.name || '-' }}</td>
              <td>
                <a :href="file.url" target="_blank">{{ file.file_name }}</a>
              </td>
              <td>{{ file.uploader?.name || '-' }}</td>
              <td>{{ formatDate(file.created_at) }}</td>
              <td>
                <v-btn icon color="error" size="small" @click="deleteAttachment(file)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
        <div v-else class="text-grey">No hay archivos adjuntos.</div>
      </v-card-text>
    </v-card>

    <!-- Diálogo de carga -->
    <v-dialog v-model="showUploadDialog" max-width="500">
      <v-card>
        <v-card-title>Subir archivo</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="uploadFile">
            <v-select
              v-model="uploadCategory"
              :items="categories"
              item-title="name"
              item-value="id"
              label="Categoría"
              :loading="loadingCategories"
              required
            />
            <v-file-input
              v-model="uploadFileInput"
              label="Archivo"
              required
              show-size
              accept="*/*"
            />
            <v-btn color="primary" type="submit" :loading="uploading">Subir</v-btn>
            <v-btn text @click="showUploadDialog = false">Cancelar</v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from '@/services/axios';

const props = defineProps({
  attachableType: { type: String, required: true }, // 'property', 'client', 'contract'
  attachableId: { type: [Number, String], required: true },
  context: { type: String, default: '' }, // para filtrar categorías si es necesario
  title: { type: String, default: 'Archivos adjuntos' },
});

// Diccionario de pluralización para las rutas
const pluralMap = {
  property: 'properties',
  client: 'clients',
  contract: 'contracts',
};

function getResourcePath() {
  switch (props.attachableType) {
  case 'client': return 'clients';
  case 'property': return 'properties';
  case 'contract': return 'contracts';
  default: throw new Error(`Tipo de recurso desconocido: ${props.attachableType}`);

}

const attachments = ref([]);
const categories = ref([]);
const loadingCategories = ref(false);
const loadingAttachments = ref(false);
const selectedCategory = ref(null);
const showUploadDialog = ref(false);
const uploadCategory = ref(null);
const uploadFileInput = ref(null);
const uploading = ref(false);

const filteredAttachments = computed(() => {
  if (!selectedCategory.value) return attachments.value;
  return attachments.value.filter(a => a.attachment_category_id === selectedCategory.value);
});

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString();
}

async function fetchCategories() {
  loadingCategories.value = true;
  try {
    const { data } = await axios.get('/api/attachment-categories', { params: { context: props.context } });
    categories.value = Array.isArray(data) ? data : (data.data || []);
  } catch (error) {
    console.error('Error fetching attachment-categories:', error);
  } finally {
    loadingCategories.value = false;
  }
}

async function fetchAttachments() {
  loadingAttachments.value = true;
  try {
    const url = `/api/${getResourcePath()}/${props.attachableId}/attachments`;
    const { data } = await axios.get(url);
    attachments.value = Array.isArray(data) ? data : (data.data || []);
  } catch (error) {
    console.error('Error fetching attachments:', error);
  } finally {
    loadingAttachments.value = false;
  }
}

function openUploadDialog() {
  showUploadDialog.value = true;
  uploadCategory.value = null;
  uploadFileInput.value = null;
}

async function uploadFile() {
  if (!uploadFileInput.value || !uploadCategory.value) return;
  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('attachment_category_id', uploadCategory.value);
    formData.append('file', uploadFileInput.value);
    const url = `/api/${getResourcePath()}/${props.attachableId}/attachments`;
    await axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    showUploadDialog.value = false;
    await fetchAttachments();
  } finally {
    uploading.value = false;
  }
}

async function deleteAttachment(file) {
  if (!confirm('¿Eliminar este archivo?')) return;
  const url = `/api/${getResourcePath()}/${props.attachableId}/attachments/${file.id}`;
  await axios.delete(url);
  await fetchAttachments();
}

onMounted(() => {
  fetchCategories();
  fetchAttachments();
});

watch(() => props.attachableId, () => {
  fetchAttachments();
});
</script>
