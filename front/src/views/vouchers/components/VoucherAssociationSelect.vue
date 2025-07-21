<template>
  <div v-if="type === 'N/C' || type === 'N/D'">
    <h3 class="text-subtitle-1 mb-2">Comprobantes Asociados</h3>

    <v-btn
      color="primary"
      variant="text"
      size="small"
      prepend-icon="mdi-plus"
      class="mb-3"
      @click="dialog = true"
    >
      Agregar comprobante
    </v-btn>

    <!-- Listado de comprobantes asociados -->
    <v-table density="compact" v-if="selected.length">
      <thead>
        <tr>
          <th>Número</th>
          <th>Fecha</th>
          <th class="text-end">Total</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in selected" :key="item.id">
          <td>{{ item.full_number }}</td>
          <td>{{ item.issue_date }}</td>
          <td class="text-end">$ {{ formatCurrency(item.total) }}</td>
          <td class="text-center">
            <v-btn
              icon="mdi-delete"
              variant="text"
              color="error"
              size="x-small"
              @click="remove(item.id)"
            />
          </td>
        </tr>
      </tbody>
    </v-table>

    <!-- Alerta si no hay ninguno -->
    <v-alert
      v-else
      type="info"
      variant="tonal"
      density="compact"
    >
      No hay comprobantes asociados.
    </v-alert>

    <!-- Modal de selección -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h6">Buscar comprobante</span>
        </v-card-title>
        <v-card-text>
          <v-autocomplete
            v-model="toAdd"
            :items="options"
            :loading="loading"
            item-value="id"
            item-title="full_number"
            return-object
            clearable
            label="Buscar comprobante"
            :disabled="loading"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props">
                <v-list-item-subtitle>
                  {{ item.raw.issue_date }} - ${{ formatCurrency(item.raw.total) }}
                </v-list-item-subtitle>
              </v-list-item>
            </template>
          </v-autocomplete>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn text @click="dialog = false">Cancelar</v-btn>
          <v-btn
            text
            color="primary"
            :disabled="!toAdd"
            @click="confirmAdd"
          >
            Agregar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  type: { type: String, required: true }, // 'N/C' o 'N/D'
  clientId: { type: [String, Number], required: true },
  modelValue: { type: Array, default: () => [] }, // array de IDs
})

const emit = defineEmits(['update:modelValue'])

const options = ref([])
const loading = ref(false)
const dialog = ref(false)
const toAdd = ref(null)
const selected = ref([])

function formatCurrency(value) {
  return Number(value || 0).toFixed(2).replace('.', ',')
}

async function fetchOptions() {
  if (!props.clientId || !props.type) return
  loading.value = true
  try {
    const { data } = await axios.get('/api/vouchers/associable', {
      params: {
        type: props.type,
        client_id: props.clientId
      }
    })
    options.value = data.data
    selected.value = data.data.filter(v => props.modelValue.includes(v.id))
  } finally {
    loading.value = false
  }
}

function confirmAdd() {
  if (!toAdd.value || selected.value.some(v => v.id === toAdd.value.id)) {
    dialog.value = false
    return
  }
  selected.value.push(toAdd.value)
  emit('update:modelValue', selected.value.map(v => v.id))
  toAdd.value = null
  dialog.value = false
}

function remove(id) {
  selected.value = selected.value.filter(v => v.id !== id)
  emit('update:modelValue', selected.value.map(v => v.id))
}

watch(() => [props.clientId, props.type], fetchOptions, { immediate: true })
</script>
