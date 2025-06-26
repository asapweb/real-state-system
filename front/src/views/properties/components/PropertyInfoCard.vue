<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <span>Información General</span>
      <v-spacer></v-spacer>
      <v-btn class="text-none" color="primary" text="Editar" variant="text" slim @click="$emit('edit')" />
    </v-card-title>

    <v-divider />

    <v-card-text>
      <!-- DATOS GENERALES -->
      <h4 class="mb-2">Datos generales</h4>
      <v-row dense>
        <v-col cols="12" md="6">
          <strong>Tipo:</strong> {{ property.property_type?.name || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Estado:</strong> {{ statusLabel(property.status) }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Partida:</strong> {{ property.tax_number || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Matrícula:</strong> {{ property.registry_number || '—' }}
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <!-- UBICACIÓN -->
      <h4 class="mb-2">Ubicación</h4>
      <v-row dense>
        <v-col cols="12" md="6">
          <strong>Calle:</strong> {{ property.street }} {{ property.number }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Piso / Dpto:</strong>
          {{ property.floor || '—' }} {{ property.apartment || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Ciudad:</strong> {{ property.city?.name || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Barrio:</strong> {{ property.neighborhood?.name || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Provincia:</strong> {{ property.state?.name || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>País:</strong> {{ property.country?.name || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Código Postal:</strong> {{ property.postal_code || '—' }}
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <!-- OTROS DATOS -->
      <h4 class="mb-2">Otros datos</h4>
      <v-row dense>
        <v-col cols="12" md="6">
          <strong>Superficie total:</strong>
          {{ property.total_area ? property.total_area + ' m²' : '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Superficie cubierta:</strong>
          {{ property.covered_area ? property.covered_area + ' m²' : '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Ambientes:</strong> {{ property.rooms || '—' }}
        </v-col>
        <v-col cols="12" md="6">
          <strong>Antigüedad:</strong> {{ property.age ? property.age + ' años' : '—' }}
        </v-col>
        <v-col cols="12">
          <strong>Observaciones:</strong><br />
          <span v-if="property.observations">{{ property.observations }}</span>
          <span v-else>—</span>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
const props = defineProps({
  property: {
    type: Object,
    required: true
  }
});

const statusLabel = (status) => {
  const map = {
    draft: 'Borrador',
    published: 'Publicado',
    archived: 'Archivado'
  };
  return map[status] || status;
};
</script>
