<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <span>Solicitudes de Alquiler</span>
      <v-spacer></v-spacer>
      <!-- Filtrar -->
      <v-btn
        icon
        variant="text"
        size="small"
        class="me-2 pa-0"
        @click="showFilters = !showFilters"
        :color="showFilters ? 'primary' : 'default'"
      >
        <v-icon size="18">mdi-filter-variant</v-icon>
      </v-btn>
      <v-btn
        class="text-none"
        color="primary"
        text="Nueva Solicitud"
        prepend-icon="mdi-plus"
        variant="text"
        slim
        @click="openCreateDialog"
      />
    </v-card-title>
    <v-card-text>
      <RentalApplicationTable :rental-offer-id="rentalOfferId" :show-filters="showFilters" ref="tableRef" />
    </v-card-text>
  </v-card>
  <div>

    <v-dialog v-model="dialog" max-width="800px">
      <v-card>
        <v-card-title>Crear nueva solicitud</v-card-title>
        <v-card-text>
          <RentalApplicationForm
            :initial-data="{ rental_offer_id: rentalOfferId }"
            :loading="saving"
            @submit="handleCreate"
            @cancel="dialog = false"
          />
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import RentalApplicationTable from '@/views/rental-applications/components/RentalApplicationTable.vue'
import RentalApplicationForm from '@/views/rental-applications/components/RentalApplicationForm.vue'
import axios from '@/services/axios'

const props = defineProps({
  rentalOfferId: { type: Number, required: true }
})

const router = useRouter()
const dialog = ref(false)
const saving = ref(false)
const tableRef = ref(null)
const showFilters = ref(false);

const openCreateDialog = () => {
  dialog.value = true
}

const handleCreate = async (formData) => {
  saving.value = true
  try {
    const { data } = await axios.post('/api/rental-applications', formData)
    dialog.value = false
    tableRef.value?.reload()
    router.push(`/rental-applications/${data.id}`)
  } catch (e) {
    console.error('Error al crear solicitud:', e)
  } finally {
    saving.value = false
  }
}
</script>
