<template>
   <!-- Tabla -->
  <h2 class="text-h6">
    Conceptos
    <v-btn
              prepend-icon="mdi-plus"
              size="small"
              color="primary"
              variant="text"
              class="ml-2"
              @click="showCreationRow = true"
              v-if="!showCreationRow"
            >
              Agregar ítem
            </v-btn>
  </h2>
  <v-table striped="even" class="no-borders">
    <thead>
      <tr>
        <th class=" font-weight-bold text-left w-15">Tipo</th>
        <th class=" font-weight-bold text-left w-30">Descripción </th>
        <th class=" font-weight-bold text-end w-1">Cantidad</th>
        <th class=" font-weight-bold text-end ">P. Unitario</th>
        <th class=" font-weight-bold text-end ">Subtotal</th>
        <th class=" font-weight-bold text-start" v-if="showTaxRate">Tipo IVA</th>
        <th class=" font-weight-bold text-end " v-if="showTaxRate">IVA</th>
        <th class=" font-weight-bold text-end " v-if="showTaxRate">Subtotal IVA</th>
        <th class=" font-weight-bold text-center">&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="8" v-if="form.items.length === 0 && !showCreationRow">
          <v-alert type="info" variant="tonal" density="compact">
            No hay conceptos para mostrar.
          </v-alert>
        </td>
      </tr>
      <!-- Ítems existentes -->
      <tr v-for="(item, index) in form.items" :key="index">
        <td>
          <v-select
            v-model="item.type"
            :items="itemTypeOptions"
            item-title="label"
            item-value="value"
            variant="underlined"
            density="compact"
            hide-details
            @update:model-value="updateItem(index)"
          />
        </td>
        <td>
          <v-text-field
            v-model="item.description"
            variant="underlined"
            density="compact"
            hide-details
            @blur="updateItem(index)"
          />
        </td>
        <td>
          <v-text-field
            v-model="item.quantity"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            variant="underlined"
            density="compact"
            hide-details
            class="text-right-field"
            @blur="updateItem(index)"
          />
        </td>
        <td>
          <v-text-field
            v-model="item.unit_price"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            variant="underlined"
            density="compact"
            hide-details
            prefix="$"
            class="text-right-field"
            @blur="updateItem(index)"
          />
        </td>
        <td class="text-end">{{ calculateItemSubtotal(item).toFixed(2) }}</td>
        <td v-if="showTaxRate">
          <TaxRateSelect
            v-model="item.tax_rate_id"
            density="compact"
            variant="underlined"
            hide-details
            @update:model-value="updateItem(index)"
          />
        </td>
        <td class="text-end" v-if="showTaxRate">{{ calculateItemVat(item).toFixed(2) }}</td>
        <td class="text-end" v-if="showTaxRate">{{ calculateItemTotal(item).toFixed(2) }}</td>
        <td class="text-center w-1">
          <v-btn icon="mdi-delete" variant="text" size="x-small" color="red-darken-2" @click="removeItem(index)" />
        </td>
      </tr>

      <!-- Fila de nuevo ítem -->
      <tr v-if="showCreationRow">
        <td>
          <v-select
            v-model="newItem.type"
            :items="itemTypeOptions"
            item-title="label"
            item-value="value"
            variant="underlined"
            density="compact"
            hide-details
          />
        </td>
        <td>
          <v-text-field
            v-model="newItem.description"
            variant="underlined"
            density="compact"
            hide-details
            placeholder="Nueva descripción"
          />
        </td>
        <td>
          <v-text-field
            v-model="newItem.quantity"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            variant="underlined"
            density="compact"
            hide-details
            class="text-right-field"
          />
        </td>
        <td>
          <v-text-field
            v-model="newItem.unit_price"
            type="number"
            min="0"
            step="0.01"
            outlined
            dense
            variant="underlined"
            density="compact"
            hide-details
            prefix="$"
            class="text-right-field"
          />
        </td>
        <td class="text-end">{{ calculateItemSubtotal(newItem).toFixed(2) }}</td>
        <td v-if="showTaxRate">
          <TaxRateSelect
            v-model="newItem.tax_rate_id"
            density="compact"
            variant="underlined"
            hide-details
          />
        </td>
        <td class="text-end" v-if="showTaxRate">{{ calculateItemVat(newItem).toFixed(2) }}</td>
        <td class="text-end" v-if="showTaxRate" >{{ calculateItemTotal(newItem).toFixed(2) }}</td>
        <td class="text-center" nowrap>
          <v-btn
            icon="mdi-check"
            size="x-small"
            color="primary"
            variant="text"
            :disabled="!canAddItem"
            @click="addItem"
          />
          <v-btn
            icon="mdi-close"
            size="x-small"
            color="grey"
            variant="text"
            @click="cancelItem"
          />
        </td>
      </tr>
    </tbody>
  </v-table>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue'
import TaxRateSelect from '@/components/forms/TaxRateSelect.vue'

const props = defineProps({
  form: Object,
  taxRates: Array,
})

const showTaxRate = computed(() => {
  return ['FAC', 'N/C', 'N/D'].includes(props.form.voucher_type_short_name) && ['A', 'B'].includes(props.form.letter)
})

const emit = defineEmits(['itemsUpdated', 'creationRowChanged'])

const showCreationRow = ref(false)
const importe = ref(0)

// Opciones de tipos de ítem basadas en VoucherItemType
const itemTypeOptions = [
  { label: 'Alquiler', value: 'rent' },
  { label: 'Seguro', value: 'insurance' },
  { label: 'Comisión', value: 'commission' },
  { label: 'Servicio', value: 'service' },
  { label: 'Penalidad', value: 'penalty' },
  { label: 'Producto', value: 'product' },
  { label: 'Ajuste', value: 'adjustment' },
  { label: 'Interés por Mora', value: 'late_fee' },
  { label: 'Impuesto', value: 'tax' },
  { label: 'Descuento', value: 'discount' },
  { label: 'Cargo', value: 'charge' },
  { label: 'Crédito', value: 'credit' },
  { label: 'Débito', value: 'debit' },
]

// Emitir cambios en showCreationRow
watch(showCreationRow, (newValue) => {
  emit('creationRowChanged', newValue)
})
const newItem = reactive({
  type: 'service',
  description: '',
  quantity: 1,
  unit_price: 0,
  tax_rate_id: null,
})

const canAddItem = computed(() =>
  newItem.type &&
  newItem.description &&
  newItem.quantity > 0 &&
  newItem.unit_price > 0 
  // &&
  // newItem.tax_rate_id !== null
)

function addItem() {
  props.form.items.push({ ...newItem })
  emit('itemsUpdated')
  resetNewItem()
  showCreationRow.value = false
}

function cancelItem() {
  resetNewItem()
  showCreationRow.value = false
}

function resetNewItem() {
  newItem.type = 'service'
  newItem.description = ''
  newItem.quantity = 1
  newItem.unit_price = 0
  newItem.tax_rate_id = null
}

function removeItem(index) {
  props.form.items.splice(index, 1)
  emit('itemsUpdated')
}

function updateItem() {
  emit('itemsUpdated')
}

function calculateItemSubtotal(item) {
  return (item.quantity || 0) * (item.unit_price || 0)
}

function calculateItemVat(item) {
  const subtotal = calculateItemSubtotal(item)
  const rate = props.taxRates.find(r => r.id === item.tax_rate_id)?.rate || 0
  // const rate = parseFloat(props.taxRates.find(r => r.id === item.tax_rate_id)?.rate || 0)
  
  
  // Si la letra es B, el IVA ya está contenido en el subtotal
  if (props.form.letter === 'B') {
    // Calcular el IVA contenido: subtotal - (subtotal / (1 + rate/100))
    return subtotal - (subtotal / (1 + rate / 100))
  }
  
  // Para otras letras, el IVA se agrega al subtotal
  return subtotal * (rate / 100)
}

function calculateItemTotal(item) {
  // Si la letra es B, el total es igual al subtotal (IVA ya incluido)
  if (props.form.letter === 'B') {
    return calculateItemSubtotal(item)
  }
  
  return calculateItemSubtotal(item) + calculateItemVat(item)
}
</script>

<style scoped>

th.w-1, td.w-1     { width: 1%; }
th.w-10, td.w-10   { width: 10%; }
th.w-15, td.w-15   { width: 15%; }
th.w-30, td.w-30   { width: 30%; }
th.w-40, td.w-40   { width: 40%; }

/* O también puedes usar */
.text-right-field :deep(input) {
  text-align: right !important;
}

.no-borders :deep(table) {
  border-collapse: collapse;
}

.no-borders :deep(td),
.no-borders :deep(th) {
  border: none !important;
}

.no-borders :deep(tr) {
  border-bottom: none !important;
}
</style>
