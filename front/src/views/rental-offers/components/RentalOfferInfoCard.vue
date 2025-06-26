<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <span>Información General</span>
      <v-spacer></v-spacer>
      <v-btn
        class="text-none"
        color="primary"
        text="Editar"
        variant="text"
        slim
        @click="$emit('edit')"
      />
    </v-card-title>
    <v-divider />
    <v-card-text>
      <v-row>
        <v-col>
          <h4 class="mb-2">Propiedad</h4>
          <div><strong>Dirección:</strong> {{ offer.property?.street || '—' }} {{ offer.property?.number || '' }}</div>
          <div><strong>Matrícula:</strong> {{ offer.property?.registry_number || '—' }}</div>
          <div><strong>Partida:</strong> {{ offer.property?.tax_code || '—' }}</div>
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <v-row>
        <v-col>
          <h4 class="mb-2">Condiciones económicas</h4>
          <div><strong>Precio:</strong> {{ formatAmount(offer.price, offer.currency) }}</div>
          <div><strong>Expensas:</strong> {{ offer.common_expenses_amount !== null ? formatAmount(offer.common_expenses_amount, offer.currency) : '—' }}</div>
          <div><strong>Duración:</strong> {{ offer.duration_months ?? '—' }} meses</div>
          <div><strong>Disponible desde:</strong> {{ offer.availability_date || '—' }}</div>
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <v-row>
        <v-col>
          <h4 class="mb-2">Otras</h4>
          <div v-if="offer.insurance_quote_amount !== null">
            <strong>Valor seguro:</strong> {{ formatAmount(offer.insurance_quote_amount, offer.currency) }}
          </div>
          <div v-if="offer.seal_required && offer.seal_amount !== null">
            <strong>Importe sellado:</strong> {{ formatAmount(offer.seal_amount, offer.seal_currency) }}
          </div>
          <div><strong>Estado:</strong> {{ statusLabel(offer.status) }}</div>
          <div><strong>Publicado:</strong> {{ offer.published_at || '—' }}</div>
          <div><strong>Admite mascotas:</strong> {{ offer.allow_pets ? 'Sí' : 'No' }}</div>
          <div><strong>Seguro:</strong> {{ offer.includes_insurance ? 'Sí' : 'No' }}</div>
          <div><strong>Política de Comisión:</strong> {{ offer.commission_policy || '—' }}</div>
          <div><strong>Política de Depósito:</strong> {{ offer.deposit_policy || '—' }}</div>
          <div v-if="offer.notes"><strong>Notas:</strong> {{ offer.notes }}</div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
defineProps({
  offer: { type: Object, required: true }
});

const statusOptions = [
  { label: 'Borrador', value: 'draft' },
  { label: 'Publicado', value: 'published' },
  { label: 'Pausado', value: 'paused' },
  { label: 'Cerrado', value: 'closed' }
];

const statusLabel = (status) =>
  statusOptions.find(s => s.value === status)?.label || status;

const formatAmount = (amount, currency) => {
  if (isNaN(Number(amount))) return '—';
  const value = Number(amount).toFixed(2);
  return currency === 'ARS' ? `$${value}` : `${value} ${currency}`;
};
</script>
