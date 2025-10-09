<template>
  <v-container fluid>
    <v-row>
      <v-col v-for="kpi in kpis" :key="kpi.title" cols="12" sm="6" md="3">
        <v-card class="pa-2" flat border>
          <div class="d-flex align-center">
            <v-avatar :icon="kpi.icon" :color="kpi.color" variant="tonal" size="40" class="mr-4"></v-avatar>
            <div>
              <div class="text-h6 font-weight-bold">{{ kpi.value }}</div>
              <div class="text-medium-emphasis">{{ kpi.title }}</div>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-sheet class="my-4" border rounded>
  <v-toolbar flat color="transparent" class="px-2">
    <v-toolbar-title class="font-weight-medium">Gestión de Liquidaciones</v-toolbar-title>

    <v-btn
      @click="filterDrawer = !filterDrawer"
      prepend-icon="mdi-filter-variant"
      variant="outlined"
      color="grey-darken-1"
    >
      Filtros
      <v-badge
        v-if="activeFilterCount > 0"
        :content="activeFilterCount"
        color="primary"
        floating
        class="ml-2"
      ></v-badge>
    </v-btn>

    <v-spacer></v-spacer>

    <v-btn variant="text" size="small">Generar Liquidación</v-btn>
    <v-btn variant="text" size="small">Emitir Borradores</v-btn>
    <v-btn variant="text" size="small">Emitir Ajustes</v-btn>
    <v-btn variant="text" size="small">Reabrir Borradores</v-btn>
  </v-toolbar>
</v-sheet>

    <v-data-table-server
      v-model="selected"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="loading"
      item-value="id"
      show-select
      @update:options="loadItems"
    >
    <template v-slot:item.total="{ item }">
      <span class="font-weight-bold">{{ item.moneda }} {{ item.total.toLocaleString('es-AR') }}</span>
    </template>
    <template v-slot:item.estado="{ item }">
      <v-chip :color="getStatusColor(item.estado)" size="small" variant="flat">
        {{ item.estado }}
      </v-chip>
    </template>
      <template v-slot:item.actions="{ item }">
        <v-icon-button icon="mdi-file-eye-outline" size="small" class="mr-1"></v-icon-button>
        <v-icon-button icon="mdi-pencil-outline" size="small" class="mr-1"></v-icon-button>
        <v-icon-button icon="mdi-delete-outline" size="small"></v-icon-button>
      </template>
    </v-data-table-server>

    <v-navigation-drawer v-model="filterDrawer" temporary location="right" width="380">
      <v-list-item title="Filtros Avanzados" subtitle="Refina los resultados de liquidación"></v-list-item>
      <v-divider></v-divider>
      <div class="pa-4">
        <v-text-field
          label="Periodo"
          placeholder="YYYY-MM"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-text-field>
        <v-select
          label="Moneda"
          :items="['ARS', 'USD', 'EUR']"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-select>
        <v-autocomplete
          label="Contrato"
          :items="['Contrato A-123', 'Contrato B-456', 'Contrato C-789']"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-autocomplete>
        <v-select
          label="Estado"
          :items="['Borrador', 'Emitido', 'Pagado', 'Ajustado']"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-select>
        <v-select
          label="Tipo de Cargo"
          :items="['Fijo', 'Variable', 'Ajuste']"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-select>
        <v-select
          label="Tipo de Servicio"
          :items="['Servicio Core', 'Soporte Premium', 'Consultoría']"
          variant="outlined"
          density="compact"
          class="mb-3"
        ></v-select>
      </div>
      <template v-slot:append>
        <div class="pa-2 d-flex">
          <v-btn block color="primary" class="mr-2">Aplicar Filtros</v-btn>
          <v-btn block variant="outlined">Limpiar</v-btn>
        </div>
      </template>
    </v-navigation-drawer>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';

// --- Estado de la UI ---
const filterDrawer = ref(false);
const activeFilterCount = ref(1); // Simula 1 filtro activo
const selected = ref([]);

// --- KPIs ---
const kpis = ref([
  { title: 'Total Facturado (ARS)', value: '$ 1.250.800', icon: 'mdi-cash-multiple', color: 'success' },
  { title: 'Liquidaciones en Borrador', value: '15', icon: 'mdi-file-edit-outline', color: 'blue-grey' },
  { title: 'Pendientes de Pago', value: '8', icon: 'mdi-file-clock-outline', color: 'warning' },
  { title: 'Ajustes Recientes', value: '4', icon: 'mdi-file-sync-outline', color: 'info' },
]);

// --- Lógica de la Tabla (Server-Side) ---
const loading = ref(false);
const totalItems = ref(0);
const serverItems = ref([]);

const headers = [
  { title: 'Contrato', key: 'contrato', align: 'start' },
  { title: 'Periodo', key: 'periodo', align: 'start' },
  { title: 'Moneda', key: 'moneda', align: 'start' },
  { title: 'Total', key: 'total', align: 'end' },
  { title: 'Estado', key: 'estado', align: 'center', sortable: false },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
];

const fakeApiCall = async ({ page, itemsPerPage, sortBy }) => {
  // Simula una llamada a la API con datos más realistas
  return new Promise(resolve => {
    setTimeout(() => {
      const start = (page - 1) * itemsPerPage;
      const items = Array.from({ length: itemsPerPage }, (_, i) => {
        const id = start + i + 1;
        return {
          id: `LIQ-${1000 + id}`,
          contrato: `Contrato ${String.fromCharCode(65 + (id % 3))}-${id * 7}`,
          periodo: '2025-10',
          moneda: ['ARS', 'USD'][id % 2],
          total: Math.random() * 50000 + 10000,
          estado: ['Borrador', 'Emitido', 'Pagado', 'Ajustado'][id % 4],
        };
      });
      resolve({ items, total: 120 }); // Simulamos un total de 120 items
    }, 800);
  });
};

const loadItems = async ({ page, itemsPerPage, sortBy }) => {
  loading.value = true;
  selected.value = [];
  const { items, total } = await fakeApiCall({ page, itemsPerPage, sortBy });
  serverItems.value = items;
  totalItems.value = total;
  loading.value = false;
};

const getStatusColor = (status) => {
  const colors = {
    'Borrador': 'blue-grey',
    'Emitido': 'primary',
    'Pagado': 'success',
    'Ajustado': 'info',
  };
  return colors[status] || 'default';
};
</script>
