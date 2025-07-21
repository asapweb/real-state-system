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
    <template #item.name="{ item }">
      {{ item.name }}
    </template>
    <template #item.type="{ item }">
      <span v-if="item.type === 'cash'">Caja</span>
      <span v-else-if="item.type === 'bank'">Banco</span>
      <span v-else-if="item.type === 'virtual'">Virtual</span>
      <span v-else>{{ item.type }}</span>
    </template>
    <template #item.currency="{ item }">
      {{ item.currency }}
    </template>
    <template #item.balance="{ item }">
      <span class="text-right" style="display: inline-block; min-width: 90px;">
        {{ formatMoney(item.balance, item.currency) }}
      </span>
    </template>
    <template #item.is_active="{ item }">
      <v-chip :color="item.is_active ? 'success' : 'error'" size="small" dark>
        {{ item.is_active ? 'Activa' : 'Inactiva' }}
      </v-chip>
    </template>
    <template #item.actions="{ item }">
      <v-btn icon size="small" @click="$emit('edit-cash-account', item)">
        <v-icon>mdi-pencil</v-icon>
      </v-btn>
      <v-btn icon size="small" color="error" @click="$emit('delete-cash-account', item)">
        <v-icon>mdi-cancel</v-icon>
      </v-btn>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { formatMoney } from '@/utils/money.js';

const props = defineProps({
  items: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  total: { type: Number, default: 0 },
  options: { type: Object, required: true },
});

const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Tipo', key: 'type' },
  { title: 'Moneda', key: 'currency' },
  { title: 'Saldo', key: 'balance', align: 'end' },
  { title: 'Estado', key: 'is_active' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
];

const itemsLength = computed(() => props.total);

const emit = defineEmits(['update:options', 'edit-cash-account', 'delete-cash-account']);

function emitOptions(newOptions) {
  emit('update:options', newOptions);
}
</script>
