<template>
  <v-data-table-server
    :headers="headers"
    :items="items"
    :loading="loading"
    :items-per-page="options.itemsPerPage"
    :page="options.page"
    :sort-by="options.sortBy"
    :items-length="itemsLength"
    @update:options="emitOptions"
    class="elevation-1"
  >
    <template #item.date="{ item }">
      {{ formatDate(item.date) }}
    </template>
    <template #item[`cash_account.name`]="{ item }">
      {{ item.cash_account?.name || '—' }}
    </template>
    <template #item.direction="{ item }">
      <v-chip :color="item.direction === 'in' ? 'success' : 'error'" size="small" dark>
        {{ item.direction === 'in' ? 'Ingreso' : 'Egreso' }}
      </v-chip>
    </template>
    <template #item.amount="{ item }">
      <span class="text-right" style="display: inline-block; min-width: 90px;">
        {{ formatMoney(item.amount, item.cash_account?.currency) }}
      </span>
    </template>
    <template #item.concept="{ item }">
      {{ item.concept }}
    </template>
    <template #item.reference="{ item }">
      {{ item.reference }}
    </template>
    <template #item.actions="{ item }">
      <v-btn icon size="small" @click="$emit('show-cash-movement', item)">
        <v-icon>mdi-eye</v-icon>
      </v-btn>
      <v-btn icon size="small" color="error" @click="$emit('delete-cash-movement', item)" :disabled="!!item.voucher_id">
        <v-icon>mdi-delete</v-icon>
      </v-btn>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { formatMoney } from '@/utils/money.js';
import { format } from 'date-fns';

const props = defineProps({
  items: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  total: { type: Number, default: 0 },
  options: { type: Object, required: true },
});

const headers = [
  { title: 'Fecha', key: 'date' },
  { title: 'Caja', key: 'cash_account.name' },
  { title: 'Dirección', key: 'direction' },
  { title: 'Monto', key: 'amount', align: 'end' },
  { title: 'Concepto', key: 'concept' },
  { title: 'Referencia', key: 'reference' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
];

const itemsLength = computed(() => props.total);

const emit = defineEmits(['update:options', 'delete-cash-movement', 'show-cash-movement']);

function emitOptions(newOptions) {
  emit('update:options', newOptions);
}

function formatDate(date) {
  if (!date) return '—';
  try {
    return format(new Date(date), 'yyyy-MM-dd');
  } catch {
    return date;
  }
}
</script>
