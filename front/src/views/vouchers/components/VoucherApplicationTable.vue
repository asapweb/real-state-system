<template>
  <v-card class="mb-4">
    <v-card-title>
      Imputaciones a Comprobantes Anteriores
      <v-spacer />
      <v-btn @click="openDialog = true" icon="mdi-plus" size="small" variant="tonal" />
    </v-card-title>

    <v-data-table
      :items="applications"
      :headers="headers"
      class="elevation-1"
      density="compact"
    >
      <template #item.amount="{ item }">
        {{ formatCurrency(item.amount) }}
      </template>
      <template #item.actions="{ index }">
        <v-btn icon="mdi-delete" variant="text" color="error" @click="remove(index)" />
      </template>
    </v-data-table>

    <v-dialog v-model="openDialog" max-width="600">
      <v-card>
        <v-card-title>Agregar Imputación</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <VoucherAutocomplete v-model="newApplication.voucher_id" :client-id="clientId" />
            </v-col>
            <v-col cols="6">
              <v-text-field v-model.number="newApplication.amount" label="Importe" type="number" />
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="newApplication.notes" label="Notas" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" @click="add">Agregar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue'
import VoucherAutocomplete from '@/views/components/VoucherAutocomplete.vue'

const props = defineProps({
  modelValue: Array,
  clientId: [Number, String, null],
})

const emit = defineEmits(['update:modelValue'])
const openDialog = ref(false)

const newApplication = ref({
  voucher_id: null,
  amount: null,
  notes: '',
})

const applications = computed({
  get: () => props.modelValue || [],
  set: (val) => emit('update:modelValue', val),
})

function add() {
  if (!newApplication.value.voucher_id || !newApplication.value.amount) return
  applications.value.push({ ...newApplication.value })
  newApplication.value = { voucher_id: null, amount: null, notes: '' }
  openDialog.value = false
}

function remove(index) {
  applications.value.splice(index, 1)
}

function formatCurrency(value) {
  return Number(value || 0).toFixed(2).replace('.', ',')
}

const headers = [
  { title: 'Comprobante', key: 'voucher_id' },
  { title: 'Importe', key: 'amount' },
  { title: 'Notas', key: 'notes' },
  { title: 'Acciones', key: 'actions', sortable: false },
]
</script>
