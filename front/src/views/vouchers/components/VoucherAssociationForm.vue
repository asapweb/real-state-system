<!-- VoucherAssociationSelect.vue -->
<template>
  <div v-if="show">
    <h2 class="text-h6">
      Comprobantes asociados
      <v-btn
        v-if="clientId"
        prepend-icon="mdi-plus"
        size="small"
        color="primary"
        variant="text"
        class="ml-2"
        @click="dialog = true"
      >
        Agregar comprobante
      </v-btn>
    </h2>

    <!-- Alerta si aún no hay cliente -->
    <v-alert
      v-if="!clientId"
      type="info"
      variant="tonal"
      density="compact"
    >
      Seleccionar un cliente para asociar comprobantes.
    </v-alert>

    <!-- Selector + tabla -->
    <template v-else>
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
            <td class="text-end">${{ formatCurrency(item.total) }}</td>
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

      <v-alert v-else type="info" variant="tonal" density="compact">
        No hay comprobantes asociados.
      </v-alert>

      <!-- Modal de búsqueda -->
      <v-dialog v-model="dialog" max-width="600">
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
              :search-input.sync="search"
              @update:search="onSearchChange"
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
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  type: { type: String, required: true },          // 'N/C' o 'N/D'
  clientId: { type: [String, Number], default: null }
})

const emit = defineEmits(['update:modelValue'])

/* ---------- reactivos ---------- */
const options  = ref([])
const loading  = ref(false)
const dialog   = ref(false)
const toAdd    = ref(null)
const selected = ref([])
const search   = ref('')

/* ---------- helpers ---------- */
const show = computed(() => ['N/C', 'N/D'].includes(props.type))

function formatCurrency(v) {
  return Number(v || 0).toFixed(2).replace('.', ',')
}

/* ---------- lógica ---------- */
async function fetchOptions(searchTerm = '') {
  if (!props.clientId || !show.value) {
    return
  }

  loading.value = true
      try {
    const params = {
      type: props.type,
      client_id: props.clientId
    }

    if (searchTerm) {
      params.search = searchTerm
    }

    const { data } = await axios.get('/api/vouchers/associable', { params })
    options.value = data.data
    selected.value = options.value.filter(v => props.modelValue.includes(v.id))
  } catch (error) {
    console.error('fetchOptions error', error)
  } finally {
    loading.value = false
  }
}

function onSearchChange(value) {
  if (value) {
    fetchOptions(value)
  } else {
    fetchOptions()
  }
}

function confirmAdd() {
  if (!toAdd.value || selected.value.some(s => s.id === toAdd.value.id)) {
    dialog.value = false
    return
  }
  selected.value.push(toAdd.value)
  emit('update:modelValue', selected.value.map(s => s.id))
  toAdd.value = null
  dialog.value = false
}

function remove(id) {
  selected.value = selected.value.filter(s => s.id !== id)
  emit('update:modelValue', selected.value.map(s => s.id))
}

/* ---------- watchers ---------- */
watch(() => [props.clientId, props.type], () => {
  fetchOptions()
}, { immediate: true })
</script>
