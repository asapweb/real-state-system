<template>
  <div>
    <v-autocomplete
      v-model="selectedClient"
      :items="clients"
      :item-title="getClientName"
      item-value="id"
      :label="label"
      :loading="isLoading"
      v-model:search-input="search"
      :clearable="clearable"
      @update:search="handleSearch"
      @update:model-value="handleSelect"
    >
      <template v-slot:no-data>
        <div class="pa-2">
          <p>No se encontraron clientes.</p>
          <v-btn color="primary" @click="openClientFormDialog">Crear nuevo cliente</v-btn>
        </div>
      </template>
    </v-autocomplete>

    <!-- Diálogo para crear un nuevo cliente -->
    <v-dialog v-model="isClientFormDialogOpen" max-width="600">
      <v-card>
        <v-card-title>Crear Cliente</v-card-title>
        <v-card-text>
          <ClientForm @submitted="handleClientCreated" @cancel="closeClientFormDialog" />
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import ClientForm from '@/views/clients/components/ClientForm.vue';

const props = defineProps({
  modelValue: { default: null },
  label: { default: 'Cliente' },
  clearable: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

const selectedClient = ref(props.modelValue);
const clients = ref([]);
const isLoading = ref(false);
const search = ref('');
const isClientFormDialogOpen = ref(false);

watch(() => props.modelValue, (val) => {
  selectedClient.value = val;
});

watch(selectedClient, (val) => {
  emit('update:modelValue', val);
});

async function fetchClients(query) {
  isLoading.value = true;
  try {
    const { data } = await axios.get('/api/clients', { params: { search: query } });
    // Si la respuesta es paginada (Laravel), usar data.data
    const arr = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
    // Si no hay búsqueda, cargar algunos clientes por defecto
    clients.value = arr;
  } catch () {
    clients.value = [];
  } finally {
    isLoading.value = false;
  }
}

// Cargar clientes iniciales al montar el componente
onMounted(() => {
  fetchClients('');
});

function handleSearch(val) {
  fetchClients(val);
}

function handleSelect(val) {
  emit('update:modelValue', val);
}

function getClientName(client) {
  if (!client) return '';
  // Soporta tanto {id, name, last_name} como {client: {id, name, last_name}}
  if (client.name) {
    return client.name + (client.last_name ? ' ' + client.last_name : '');
  }
  if (client.client && client.client.name) {
    return client.client.name + (client.client.last_name ? ' ' + client.client.last_name : '');
  }
  return client.id ? `Cliente #${client.id}` : '';
}

function openClientFormDialog() {
  isClientFormDialogOpen.value = true;
}

function closeClientFormDialog() {
  isClientFormDialogOpen.value = false;
}

function handleClientCreated(newClient) {
  isClientFormDialogOpen.value = false;
  // Agregar el nuevo cliente a la lista y seleccionarlo
  clients.value.push(newClient);
  selectedClient.value = newClient.id;
}
</script>
