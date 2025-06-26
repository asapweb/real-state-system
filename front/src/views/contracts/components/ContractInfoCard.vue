<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <span>Información General</span>
      <v-spacer></v-spacer>
      <div class="d-flex">
        <v-btn
          class="text-none"
          color="primary"
          text="Editar"
          variant="text"
          slim
          @click="emit('edit')"
        ></v-btn>
      </div>
    </v-card-title>
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <strong>Propiedad:</strong><br />
          <RouterLink
            :to="`/properties/${contract.property?.id}`"
            class="text-primary text-decoration-none"
          >
            {{ contract.property?.street }} {{ contract.property?.number }}
          </RouterLink>
        </v-col>

        <v-col cols="6" md="3">
          <strong>Desde:</strong><br />
          {{ formatDate(contract.start_date) }}
        </v-col>

        <v-col cols="6" md="3">
          <strong>Hasta:</strong><br />
          {{ formatDate(contract.end_date) }}
        </v-col>

        <v-col cols="6" md="3">
          <strong>Importe mensual:</strong><br />
          {{ formatMoney(contract.monthly_amount, contract.currency) }}
        </v-col>

        <v-col cols="6" md="3">
          <strong>Día de pago:</strong><br />
          {{ contract.payment_day ?? '—' }}
        </v-col>

        <v-col cols="6" md="3">
          <strong>Comisión:</strong><br />
          <span v-if="contract.commission_type === 'none'">Sin comisión</span>
          <span v-else>{{ formatMoney(contract.commission_amount, contract.currency) }}</span>
        </v-col>

        <v-col cols="6" md="3">
          <strong>Estado:</strong><br />
          <v-chip :color="statusColor(contract.status)" size="small" variant="flat">
            {{ statusLabel(contract.status) }}
          </v-chip>
        </v-col>

        <v-col cols="12">
          <strong>Notas:</strong><br />
          <span v-if="contract.notes">{{ contract.notes }}</span>
          <span v-else class="text-grey">Sin notas</span>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { formatMoney } from '@/utils/money';
import { formatDate } from '@/utils/date-formatter';

const props = defineProps({
  contract: { type: Object, required: true }
});

const emit = defineEmits(['edit']);

const statusLabel = (status) => {
  const map = {
    draft: 'Borrador',
    active: 'Vigente',
    closed: 'Finalizado',
    canceled: 'Cancelado'
  };
  return map[status] || status;
};

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'grey';
    case 'active': return 'green';
    case 'closed': return 'blue-grey';
    case 'canceled': return 'red';
    default: return 'default';
  }
};
</script>
