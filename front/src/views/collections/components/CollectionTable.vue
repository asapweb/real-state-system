<template>
  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="collections"
    :items-length="total"
    :loading="loading"
    item-value="contract_id"
    class="elevation-1"
    @update:options="fetchCollections"
  >
    <!-- Contrato -->
    <template #[`item.contract_code`]="{ item }">
      <RouterLink
        :to="`/contracts/${item.contract_id}`"
        class="text-primary text-decoration-none font-weight-bold"
      >
        {{ item.contract_code }}
      </RouterLink>
    </template>

    <!-- Período -->
    <template #[`item.period`]="{ item }">
      {{ item.period }}
    </template>

    <!-- Renta -->
    <template #[`item.rent_amount`]="{ item }">
      <span v-if="item.rent_amount !== null">
        {{ formatMoney(item.rent_amount, item.currency) }}
      </span>
      <v-chip v-else size="small" color="warning">
        Ajuste pendiente
      </v-chip>
    </template>

    <!-- Gastos (multi-moneda) -->
    <template #[`item.expenses`]="{ item }">
      <div v-if="item.expenses.length">
        <div v-for="expense in item.expenses" :key="expense.currency">
          {{ expense.currency }}: {{ formatMoney(expense.amount, expense.currency) }}
        </div>
      </div>
      <span v-else class="text-disabled">Sin gastos</span>
    </template>

    <!-- Estado -->
    <template #[`item.status`]="{ item }">
      <v-chip size="small" :color="statusColor(item.status)">
        {{ statusLabel(item.status) }}
      </v-chip>
    </template>

    <!-- Acciones -->
    <template #[`item.actions`]="{ item }">
      <div class="d-flex align-center">
        <!-- Aplicar ajuste -->
        <v-btn
          v-if="item.status === 'pending_adjustment'"
          size="small"
          color="warning"
          variant="outlined"
          @click="$emit('apply-adjustment', item)"
        >
          <v-icon start size="small">mdi-tune</v-icon>
          Aplicar ajuste
        </v-btn>

        <!-- Editar cobranza -->
        <v-btn
          v-else-if="['pending','draft'].includes(item.status)"
          size="small"
          color="primary"
          variant="flat"
          @click="$emit('open-editor', item)"
        >
          <v-icon start size="small">mdi-file-document-edit</v-icon>
          Editar
        </v-btn>

        <!-- Ver cobranza emitida -->
        <v-btn
          v-if="['issued'].includes(item.status) || item.status === 'draft'"
          size="small"
          color="grey"
          variant="outlined"
          @click="$router.push(`/collections/${item.contract_id}/view?period=${item.period}`)"
        >
          <v-icon start size="small">mdi-eye</v-icon>
          Ver
        </v-btn>
      </div>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from '@/services/axios'
import { formatMoney } from '@/utils/money'
import { useSnackbar } from '@/composables/useSnackbar'
import { statusLabel, statusColor } from '@/utils/collections-formatter'

const props = defineProps({ filters: Object })
const emit = defineEmits(['apply-adjustment', 'open-editor', 'view-issued'])

const snackbar = useSnackbar()

const collections = ref([])
const total = ref(0)
const loading = ref(false)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'contract_code', order: 'asc' }],
})

const headers = computed(() => [
  { title: 'Contrato', key: 'contract_code', sortable: true },
  { title: 'Inquilino', key: 'tenant_name', sortable: false },
  { title: 'Período', key: 'period', sortable: true },
  { title: 'Renta', key: 'rent_amount', sortable: false },
  { title: 'Gastos', key: 'expenses', sortable: false },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
])

const fetchCollections = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/collections', {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
        ...props.filters,
      },
    })
    collections.value = data.data
    total.value = data.meta.total
  } catch (e) {
    console.error(e)
    snackbar.error('Error al cargar las cobranzas')
  } finally {
    loading.value = false
  }
}

watch(props.filters, () => {
  options.page = 1
  fetchCollections()
})

onMounted(fetchCollections)
defineExpose({ fetchCollections })
</script>
