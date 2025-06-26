<template>
  <v-data-table :items="items" :headers="headers" class="mb-4" hide-default-footer>
    <template #[`item.unit_price`]="{ item }">
      {{ formatMoney(item.unit_price) }}
    </template>

    <template #[`item.amount`]="{ item }">
      {{ formatMoney(item.amount) }}
    </template>

    <template #[`item.actions`]="{ index }">
      <v-btn icon size="small" @click="$emit('edit', items[index], index)">
        <v-icon>mdi-pencil</v-icon>
      </v-btn>
      <v-btn icon size="small" @click="$emit('delete', index)">
        <v-icon color="red">mdi-delete</v-icon>
      </v-btn>
    </template>

    <template #bottom>
      <v-divider class="my-2" />
      <div class="text-end px-4">
        <strong>Total: {{ formatMoney(totalAmount) }}</strong>
      </div>
    </template>
  </v-data-table>
</template>

<script setup>
import { computed } from 'vue'
import { formatMoney } from '@/utils/money'

const props = defineProps({
  items: Array,
})

const headers = [
  { title: 'Tipo', key: 'type' },
  { title: 'Descripción', key: 'description' },
  { title: 'Cantidad', key: 'quantity' },
  { title: 'Precio Unitario', key: 'unit_price' },
  { title: 'Subtotal', key: 'amount' },
  { title: '', key: 'actions', sortable: false },
]

const totalAmount = computed(() => {
  return props.items.reduce((acc, item) => acc + Number(item.amount || 0), 0)
})
</script>
