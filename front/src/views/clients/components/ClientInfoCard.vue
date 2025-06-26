<template>
  <VCard>
    <v-card-title class="d-flex align-center">
      <span>Información General</span>
      <v-spacer></v-spacer>
      <v-btn class="text-none" color="primary" text="Editar" variant="text" slim @click="$emit('edit')" />
    </v-card-title>
    <v-divider />

    <VCardText>
      <VRow>
        <VCol cols="12" md="6">
          <p><strong>Tipo:</strong> {{ typeLabel(client.type) }}</p>
          <p><strong>Nombre:</strong> {{ client.name }}</p>
          <p v-if="client.type === 'individual'"><strong>Apellido:</strong> {{ client.last_name }}</p>
        </VCol>
        <VCol cols="12" md="6">
          <p><strong>Email:</strong> {{ client.email || '—' }}</p>
          <p><strong>Teléfono:</strong> {{ client.phone || '—' }}</p>
          <p><strong>Dirección:</strong> {{ client.address || '—' }}</p>
        </VCol>
      </VRow>

      <VDivider class="my-4" />

      <VRow>
        <VCol cols="12" md="6">
          <p><strong>Tipo de Documento:</strong> {{ client.document_type?.name || '—' }}</p>
          <p><strong>N° de Documento:</strong> {{ client.document_number || '—' }}</p>
          <p><strong>Sin documento:</strong> {{ client.no_document ? 'Sí' : 'No' }}</p>
        </VCol>
        <VCol cols="12" md="6">
          <p><strong>Tipo Doc. Fiscal:</strong> {{ client.tax_document_type?.name || '—' }}</p>
          <p><strong>CUIT / CUIL:</strong> {{ client.tax_document_number || '—' }}</p>
          <p><strong>Condición Fiscal:</strong> {{ client.tax_condition?.name || '—' }}</p>
        </VCol>
      </VRow>

      <VDivider class="my-4" />

      <VRow>
        <VCol cols="12" md="6">
          <p><strong>Estado Civil:</strong> {{ client.civil_status?.name || '—' }}</p>
        </VCol>
        <VCol cols="12" md="6">
          <p><strong>Nacionalidad:</strong> {{ client.nationality?.name || '—' }}</p>
        </VCol>
      </VRow>

      <VRow v-if="client.notes">
        <VCol cols="12">
          <p><strong>Notas:</strong></p>
          {{ client.notes }}
        </VCol>
      </VRow>
    </VCardText>
    <v-divider />
  </VCard>
</template>

<script setup>
const props = defineProps({
  client: { type: Object, required: true }
});

const typeLabel = (type) =>
  type === 'individual' ? 'Persona física' : 'Empresa';
</script>
