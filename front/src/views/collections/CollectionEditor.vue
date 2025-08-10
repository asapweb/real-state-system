<template>
  <div>
    <h2 class="mb-4">
      Editor de Cobranza - {{ contract?.main_tenant?.client?.full_name }} ({{ period }})
    </h2>

    <v-card class="mb-4">
      <v-card-text>
        <div><strong>Contrato:</strong> CON-{{ contract?.id }}</div>
        <div><strong>Inquilino:</strong> {{ contract?.main_tenant?.client?.full_name }}</div>
        <div><strong>Período:</strong> {{ period }}</div>
        <div><strong>Estado actual:</strong> {{ statusLabel(status) }}</div>
        <div v-if="hasChanges" class="mt-2 text-warning">
          <v-icon size="16" class="me-1">mdi-alert-circle</v-icon>
          Cambios no guardados
        </div>
      </v-card-text>
    </v-card>

    <v-switch
      v-model="hideIncluded"
      label="Ocultar ítems incluidos en vouchers"
      color="primary"
      class="mb-4"
    />

    <v-alert
      v-if="!hasSelectableItems && !hasChanges"
      type="info"
      class="mb-4"
      text
    >
      No hay ítems disponibles para incluir en esta cobranza. Todos los cargos ya han sido facturados.
    </v-alert>

    <v-card
      v-for="group in groupedItems"
      :key="group.currency"
      class="mb-4"
    >
      <v-card-title>
        <v-icon size="16" class="me-2" :color="group.items.every(i => i.locked) ? 'grey' : group.items.some(i => i.selected) ? 'primary' : 'warning'">
          {{ group.items.every(i => i.locked) ? 'mdi-lock' : group.items.some(i => i.selected) ? 'mdi-check-circle-outline' : 'mdi-alert-outline' }}
        </v-icon>
        {{ group.currency }} — Total: {{ formatCurrency(group.total, group.currency) }}
        <v-tooltip activator="parent" location="bottom">
          <span>
            {{ group.items.every(i => i.locked)
              ? 'Todos los ítems están incluidos en vouchers'
              : group.items.some(i => i.selected)
              ? 'Al menos un ítem será incluido en esta cobranza'
              : 'Hay ítems disponibles no seleccionados' }}
          </span>
        </v-tooltip>
      </v-card-title>
      <v-divider />

      <v-data-table
        :headers="headers"
        :items="group.items"
        item-value="id"
        class="mb-4"
      >
        <template #[`item.select`]="{ item }">
          <v-checkbox
            :model-value="item.selected"
            @update:model-value="val => updateSelection(item, val)"
            :disabled="item.locked"
            density="compact"
          />
        </template>

        <template #[`item.amount`]="{ item }">
          <v-text-field
            v-model="item.amount"
            :disabled="item.locked"
            type="number"
            density="compact"
            hide-details
          />
        </template>

        <template #[`item.state`]="{ item }">
          <v-chip
            size="small"
            :color="item.locked ? 'grey' : 'warning'"
            class="d-flex align-center"
          >
            <v-icon v-if="item.locked" size="14" class="me-1">mdi-lock</v-icon>
            {{ item.state_label }}
          </v-chip>
        </template>
      </v-data-table>
    </v-card>

    <v-alert
      v-if="generatedCurrencies.length > 0 && hasSelectableItems"
      type="info"
      class="mb-4"
      border="start"
      icon="mdi-currency-usd"
    >
      Se generará una cobranza en las siguientes monedas:
      <strong>{{ generatedCurrencies.join(', ') }}</strong>
    </v-alert>

    <v-alert
      v-if="hasUnselectedSelectableItems"
      type="warning"
      class="mb-4"
      text
    >
      Hay ítems disponibles que no están seleccionados para esta cobranza.
    </v-alert>

    <div class="d-flex justify-end">
      <v-btn color="grey" variant="outlined" class="me-2" @click="$router.push('/collections')">
        Cancelar
      </v-btn>
      <v-btn color="primary" @click="save" :loading="saving" :disabled="!canSave">
        Guardar cambios
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import axios from '@/services/axios'
import { useSnackbar } from '@/composables/useSnackbar'
import { statusLabel } from '@/utils/collections-formatter'

const props = defineProps({
  contractId: { type: Number, required: true },
  period: { type: String, required: true },
})

const snackbar = useSnackbar()
const contract = ref(null)
const items = ref([])
const originalItems = ref([])
const status = ref('draft')
const saving = ref(false)
const hideIncluded = ref(false)

const headers = [
  { title: '', key: 'select', sortable: false, width: 40 },
  { title: 'Descripción', key: 'description', sortable: false },
  { title: 'Monto', key: 'amount', sortable: false },
  { title: 'Moneda', key: 'currency', sortable: false },
  { title: 'Estado', key: 'state', sortable: false },
]

const groupedItems = computed(() => {
  let filtered = hideIncluded.value
    ? items.value.filter((i) => !i.locked)
    : items.value

  const groups = {}
  filtered.forEach((item) => {
    if (!groups[item.currency]) {
      groups[item.currency] = { currency: item.currency, total: 0, items: [] }
    }
    groups[item.currency].items.push(item)
    groups[item.currency].total += parseFloat(item.amount || 0)
  })
  return Object.values(groups)
})

const formatCurrency = (value, currency) => {
  if (value === null || value === undefined) return '-'
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: currency || 'ARS',
    minimumFractionDigits: 2,
  }).format(value)
}

const updateSelection = (item, selected) => {
  item.selected = selected
}

const hasChanges = computed(() => {
  return items.value.some(item => {
    const original = originalItems.value.find(o => o.id === item.id)
    return original && item.selected !== original.selected
  })
})

const hasSelectableItems = computed(() => {
  return items.value.some(item => !item.locked && item.selected)
})

const canSave = computed(() => {
  return hasSelectableItems.value || hasChanges.value
})

const hasUnselectedSelectableItems = computed(() => {
  return items.value.some(item => !item.locked && !item.selected)
})

const generatedCurrencies = computed(() => {
  const currencies = new Set()
  items.value.forEach(item => {
    if (!item.locked && item.selected) {
      currencies.add(item.currency)
    }
  })
  return Array.from(currencies)
})

onBeforeRouteLeave((to, from, next) => {
  if (hasChanges.value && !saving.value) {
    const answer = window.confirm('Tienes cambios sin guardar. ¿Estás seguro que deseas salir?')
    if (!answer) return next(false)
  }
  next()
})

onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/collections/${props.contractId}/editor`, {
      params: { period: props.period },
    })

    contract.value = data.contract
    status.value = data.status

    const flat = []
    data.expenses.forEach((group) => {
      group.expenses.forEach((e) => {
        flat.push({
          id: e.id,
          type: e.type,
          description: e.description,
          amount: e.amount,
          currency: e.currency,
          selected: e.selected,
          locked: e.locked,
          state_label: e.state_label,
          voucher_id: e.voucher_id,
        })
      })
    })

    items.value = JSON.parse(JSON.stringify(flat))
    originalItems.value = JSON.parse(JSON.stringify(flat))

  } catch (err) {
    snackbar.error('Error al cargar el editor de cobranza.')
  }
})

const save = async () => {
  try {
    saving.value = true
    console.log('items',items.value)

    const payload = {
      period: props.period,
      items: items.value
        .filter((i) => i.selected && !i.locked)
        .map((i) => ({ id: i.id, amount: i.amount, type: i.type, description: i.description })),
    }
    await axios.post(`/api/collections/${props.contractId}/generate`, payload)
    snackbar.success('Cobranza generada/actualizada correctamente.')
    window.location.href = '/collections'
  } catch (err) {
    snackbar.error('Error al guardar la cobranza.')
  } finally {
    saving.value = false
  }
}
</script>
