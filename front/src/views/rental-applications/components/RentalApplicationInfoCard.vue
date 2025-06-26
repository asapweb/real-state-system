<template>
  <v-card v-if="application">
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
                @click="goToEdit"
              ></v-btn>
              </div>
          </v-card-title>
    <v-card-text>
      <v-row>
        <!-- Propiedad -->
        <v-col cols="12" md="6">
          <strong>Propiedad:</strong>
          <div>
            {{ application.property?.street }} {{ application.property?.number }}
            - {{ application.property?.city?.name }}
          </div>
        </v-col>

        <!-- Postulante -->
        <v-col cols="12" md="6">
          <strong>Postulante:</strong>
          <div>
            {{ application.applicant?.last_name }} {{ application.applicant?.name }}
          </div>
        </v-col>

        <!-- Estado -->
        <v-col cols="12" md="6">
          <strong>Estado:</strong>
          <v-chip :color="statusColor(application.status)" variant="flat" class="mt-1">
            {{ statusLabel(application.status) }}
          </v-chip>
        </v-col>

        <!-- Seña -->
        <v-col cols="6" md="3">
          <strong>Seña:</strong>
          <div>{{ formatMoney(application.reservation_amount, application.currency) }}</div>
        </v-col>

        <v-col cols="6" md="3">
          <strong>Moneda:</strong>
          <div>{{ application.currency }}</div>
        </v-col>

        <!-- Seguro -->
        <v-col cols="12" md="6">
          <strong>Responsable del seguro:</strong>
          <div>{{ insuranceLabel(application.insurance_responsible) }}</div>
        </v-col>

        <v-col cols="12" md="6">
          <strong>Desde cuándo se requiere:</strong>
          <div>{{ application.insurance_required_from || '—' }}</div>
        </v-col>

        <!-- Notas -->
        <v-col cols="12">
          <strong>Notas:</strong>
          <div>{{ application.notes || '—' }}</div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useRouter } from 'vue-router'

const props = defineProps({
  application: { type: Object, required: true, default: null }
})
const router = useRouter()

const statusLabel = (status) => {
  switch (status) {
    case 'draft': return 'Borrador'
    case 'under_review': return 'En evaluación'
    case 'approved': return 'Aprobado'
    case 'rejected': return 'Rechazado'
    default: return status
  }
}

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'grey'
    case 'under_review': return 'blue'
    case 'approved': return 'green'
    case 'rejected': return 'red'
    default: return 'default'
  }
}

const insuranceLabel = (val) => {
  switch (val) {
    case 'tenant': return 'Inquilino'
    case 'owner': return 'Propietario'
    case 'agency': return 'Inmobiliaria'
    default: return '—'
  }
}

const formatMoney = (amount, currency) => {
  if (!amount) return '—'
  const value = parseFloat(amount).toLocaleString('es-AR', { minimumFractionDigits: 2 })
  return currency === 'USD' ? `U$D ${value}` : `AR$ ${value}`
}
const goToEdit = () => {
  router.push(`/rental-applications/${props.application.id}/edit`)
}

</script>
