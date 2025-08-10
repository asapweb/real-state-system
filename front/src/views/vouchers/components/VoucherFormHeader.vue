<template>

      <v-row>
        <v-col cols="12" md="4">
          <BookletSelect
          hide-details="auto"
            :model-value="modelValue"
            @update:modelValue="$emit('update:modelValue', $event)"
            :voucherType="form.voucher_type || defaultType || null"
            :letter="null"
          />
        </v-col>
        <v-col cols="12" md="4">
          <CurrencySelect hide-details="auto" v-model="form.currency" required density="compact" />
        </v-col>
        <v-col cols="12" md="2" :offset-md="['FAC', 'N/C', 'N/D', 'COB'].includes(form.voucher_type_short_name || defaultType?.toUpperCase()) ? 0 : 2">
          <v-text-field
            hide-details="auto"
            v-model="form.issue_date"
            label="Fecha de emisión"
            type="date"
            required
          />
        </v-col>
        <v-col cols="12" md="2" v-if="['FAC', 'N/C', 'N/D','COB'].includes(form.voucher_type_short_name || defaultType?.toUpperCase())">
          <v-text-field
            hide-details="auto"
            v-model="form.due_date"
            label="Vencimiento"
            type="date"
          />
        </v-col>
      </v-row>
      <v-row v-if="['FAC', 'N/C', 'N/D'].includes(form.voucher_type_short_name || defaultType?.toUpperCase())">
        <v-col cols="12" md="5">
          <v-autocomplete
            hide-details="auto"
            v-model="form.afip_operation_type_id"
            :items="afipOperationTypes"
            item-title="name"
            item-value="id"
            label="Tipo de operación AFIP"
            :loading="loadingAfipTypes"
            clearable
            density="compact"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            hide-details="auto"
            v-model="form.service_date_from"
            label="Servicio desde"
            type="date"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field
            hide-details="auto"
            v-model="form.service_date_to"
            label="Servicio hasta"
            type="date"
          />
        </v-col>
      </v-row>

      <!-- Sección: Cliente -->
      <v-row class="mt-10">
        <v-col cols="12" md="5">
          <ClientAutocomplete
            v-model="form.client_id"
            label="Seleccionar Cliente"
            :loading="loadingClients"
            required
            @change="onClientChanged"
          />
        </v-col>

          <v-col offset-md="1" cols="12" md="4">
            <ContractAutocomplete
              v-model="form.contract_id"
              :client-id="form.client_id"
              @update:modelValue="$emit('contract-selected', $event)"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-text-field
              hide-details="auto"
              v-model="form.period"
              label="Período (YYYY-MM)"
              placeholder="Ej: 2024-07"
            />
          </v-col>
          <v-col cols="12" md="12" v-if="form.client_id" class="px-5">
              <v-row class="bg-grey-lighten-5 pa-4 rounded-lg">
        <v-col cols="12" md="6">
          <v-text-field hide-details="auto" v-model="form.client_name" label="Nombre del Cliente" variant="underlined" />
        </v-col>
        <v-col cols="12" md="2">
          <DocumentTypesSelect
            hide-details="auto"
            v-model="form.client_document_type_id"
            :client-type="null"
            density="compact"
            variant="underlined"
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-text-field
            hide-details="auto"
            v-model="form.client_document_number"
            label="Nro. Documento"
            variant="underlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field hide-details="auto" v-model="form.client_address" label="Dirección" variant="underlined" />
        </v-col>
        <v-col cols="12" md="6">
          <TaxConditionsSelect
            hide-details="auto"
            v-model="form.client_iva_condition_id"
            density="compact"
            variant="underlined"
          />
        </v-col>
      </v-row>
          </v-col>

      </v-row>


</template>

<script setup>
import BookletSelect from './BookletSelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue'
import DocumentTypesSelect from '@/components/forms/DocumentTypesSelect.vue'
import TaxConditionsSelect from '@/components/forms/TaxConditionsSelect.vue'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'
import { watch, ref, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  form: Object,
  modelValue: Object,
  clients: Array,
  loadingClients: Boolean,
  defaultType: String
})

const emit = defineEmits(['contract-selected', 'update:modelValue', 'client-changed'])

// Watcher para detectar cambios en el cliente
watch(
  () => props.form.client_id,
  (newClientId, oldClientId) => {
    if (newClientId !== oldClientId) {
      emit('client-changed', newClientId)
    }
  }
)

function onClientChanged(newClientId) {
  // Limpiar el contrato seleccionado cuando cambie el cliente
  props.form.contract_id = null
  emit('client-changed', newClientId)
}

const afipOperationTypes = ref([])
const loadingAfipTypes = ref(false)

async function fetchAfipOperationTypes() {
  loadingAfipTypes.value = true
  try {
    const { data } = await axios.get('/api/afip-operation-types', { params: { per_page: 50 } })
    afipOperationTypes.value = data.data || data
    
    // Establecer el valor por defecto si no hay un valor seleccionado
    if (!props.form.afip_operation_type_id) {
      const defaultType = afipOperationTypes.value.find(type => type.is_default === true)
      if (defaultType) {
        props.form.afip_operation_type_id = defaultType.id
      }
    }
  } finally {
    loadingAfipTypes.value = false
  }
}

onMounted(() => {
  fetchAfipOperationTypes()
})
</script>
