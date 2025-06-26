<template>
  <v-card>
    <v-card-title>Estado de Servicios</v-card-title>
    <v-data-table
      :items="services"
      :headers="headers"
      :loading="loading"
      item-value="id"
      class="elevation-1"
      density="compact"
    >
      <template #[`item.service`]="{ item }">
        {{ item.property_service?.service_type }}
      </template>

      <template #[`item.is_active`]="{ item }">
        <v-switch
          v-model="item.is_active"
          hide-details
          density="compact"
          @change="save(item)"
        />
      </template>

      <template #[`item.has_debt`]="{ item }">
        <v-switch
          v-model="item.has_debt"
          hide-details
          density="compact"
          @change="save(item)"
        />
      </template>

      <template #[`item.debt_amount`]="{ item }">
        <v-text-field
          v-model="item.debt_amount"
          type="number"
          density="compact"
          hide-details
          :disabled="!item.has_debt"
          @blur="save(item)"
        />
      </template>

      <template #[`item.paid_by`]="{ item }">
        <v-select
          v-model="item.paid_by"
          :items="payerOptions"
          item-title="label"
          item-value="value"
          density="compact"
          hide-details
          @update:model-value="save(item)"
        />
      </template>

      <template #[`item.notes`]="{ item }">
        <v-text-field
          v-model="item.notes"
          density="compact"
          hide-details
          @blur="save(item)"
        />
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  rentalOfferId: { type: Number, required: true }
})

const services = ref([])
const loading = ref(false)

const headers = [
  { title: 'Servicio', key: 'service', sortable: false },
  { title: 'Activo', key: 'is_active', sortable: false },
  { title: 'Con deuda', key: 'has_debt', sortable: false },
  { title: 'Monto deuda', key: 'debt_amount', sortable: false },
  { title: 'Pagado por', key: 'paid_by', sortable: false },
  { title: 'Observaciones', key: 'notes', sortable: false }
]

const payerOptions = [
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
  { label: 'Inmobiliaria', value: 'agency' }
]

const fetchServices = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/rental-offers/${props.rentalOfferId}/services`)
    services.value = data.data
  } catch (e) {
    console.error('Error al obtener estado de servicios:', e)
  } finally {
    loading.value = false
  }
}

const save = async (item) => {
  try {
    await axios.put(`/api/rental-offer-service-statuses/${item.id}`, item)
  } catch (e) {
    console.error('Error al guardar cambios:', e)
  }
}

onMounted(fetchServices)
watch(() => props.rentalOfferId, fetchServices)
defineExpose({ reload: fetchServices })
</script>
