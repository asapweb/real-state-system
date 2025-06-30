<template>
  <div class="mb-8">
    <div class="d-flex justify-space-between align-center">
      <h2>Cobranzas</h2>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="goToManual">
        Nueva Cobranza Manual
      </v-btn>
    </div>
    <p class="text-medium-emphasis mt-1">
      Permite registrar montos adeudados por parte de los clientes (principalmente inquilinos)<br />
      Estas cobranzas pueden originarse de forma automática (Generación mensual) o manual.
    </p>
  </div>

  <v-row class="mb-6">
    <v-col cols="12" md="2">
      <v-text-field
        v-model="filters.id"
        label="N° de Comprobante"
        type="number"
        clearable
        hide-details
      />
    </v-col>

    <v-col cols="12" md="4">
      <ClientAutocomplete v-model="filters.client_id" label="Cliente" />
    </v-col>

    <v-col cols="12" md="4">
      <ContractAutocomplete
        v-model="filters.contract_id"
        :client-id="filters.client_id"
        label="Contrato"
      />
    </v-col>

    <v-col cols="12" md="2">
      <v-select
        v-model="filters.status"
        :items="statusOptions"
        item-title="label"
        item-value="value"
        label="Estado"
        clearable
        hide-details
      />
    </v-col>

    <v-col cols="12" md="3">
      <v-text-field
        v-model="filters.issue_date_start"
        label="Desde fecha"
        type="date"
        clearable
        hide-details
      />
    </v-col>
    <v-col cols="12" md="3">
      <v-text-field
        v-model="filters.issue_date_end"
        label="Hasta fecha"
        type="date"
        clearable
        hide-details
      />
    </v-col>
    <v-col cols="12" md="3">
      <v-text-field
        v-model="filters.due_date_start"
        label="Desde vencimiento"
        type="date"
        clearable
        hide-details
      />
    </v-col>
    <v-col cols="12" md="3">
      <v-text-field
        v-model="filters.due_date_end"
        label="Hasta vencimiento"
        type="date"
        clearable
        hide-details
      />
    </v-col>
  </v-row>

  <v-row>
    <v-col cols="12">
      <CollectionTable ref="tableRef" :filters="filters" @error="handleError" />

    </v-col>
  </v-row>
</template>

<script setup>
import { ref, reactive } from 'vue'
import CollectionTable from './components/CollectionTable.vue'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import { useRouter } from 'vue-router'

import { useSnackbar } from '@/composables/useSnackbar'

const filters = reactive({
  id: null,
  client_id: null,
  contract_id: null,
  status: null,
  issue_date_start: null,
  issue_date_end: null,
  due_date_start: null,
  due_date_end: null,
})

const statusOptions = [
  { label: 'Pendiente', value: 'pending' },
  { label: 'Pagada', value: 'paid' },
  { label: 'Vencida', value: 'expired' },
  { label: 'Anulada', value: 'canceled' },
]

const tableRef = ref(null)
const snackbar = useSnackbar()

const router = useRouter()

const goToManual = () => {
  router.push('/collections/create')
}

const handleError = (message) => {
  snackbar.error(message)
}
</script>
