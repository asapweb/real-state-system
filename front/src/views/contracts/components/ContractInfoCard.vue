<template>
  <v-card>
    <v-card-title class="d-flex ">
      <div>Información General
        <v-chip :color="statusColor(contract.status)" size="small" variant="flat">
            {{ statusLabel(contract.status) }}
          </v-chip>
      </div>
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
        <v-col cols="12" md="4">
          <strong>Propiedad:</strong><br />
          <RouterLink
            :to="`/properties/${contract.property?.id}`"
            class="text-primary text-decoration-none"
          >
            {{ contract.property?.street }} {{ contract.property?.number }}
          </RouterLink>
        </v-col>

        <v-col cols="6" md="2">
          <strong>Desde:</strong><br />
          {{ formatDate(contract.start_date) }}
        </v-col>

        <v-col cols="6" md="2">
          <strong>Hasta:</strong><br />
          {{ formatDate(contract.end_date) }}
        </v-col>

        <v-col cols="6" md="2">
          <strong>Importe mensual:</strong><br />
          {{ formatMoney(contract.monthly_amount, contract.currency) }}
        </v-col>

        <v-col cols="6" md="2">
          <strong>Día de pago:</strong><br />
          {{ contract.payment_day ?? '—' }}
        </v-col>        
        <v-col cols="6" md="2">
          <strong>Punitorio:</strong><br />
          {{ contract.penalty_type ?? '—' }}
        </v-col>
        <v-col cols="6" md="2">
          <strong>Punitorio valor:</strong><br />
          {{ contract.penalty_value ?? '—' }}
        </v-col>
        <v-col cols="6" md="2">
          <strong>Punitorio días de gracia:</strong><br />
          {{ contract.penalty_grace_days ?? '—' }}
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { formatMoney } from '@/utils/money'
import { formatDate } from '@/utils/date-formatter'

defineProps({
  contract: { type: Object, required: true },
})

const emit = defineEmits(['edit'])

const statusLabel = (status) => {
  const map = {
    draft: 'Borrador',
    active: 'Vigente',
    closed: 'Finalizado',
    canceled: 'Cancelado',
  }
  return map[status] || status
}

const statusColor = (status) => {
  switch (status) {
    case 'draft':
      return 'grey'
    case 'active':
      return 'green'
    case 'closed':
      return 'blue-grey'
    case 'canceled':
      return 'red'
    default:
      return 'default'
  }
}
</script>
