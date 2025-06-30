  <template>
  <v-data-table-server
    v-model:items-per-page="options.itemsPerPage"
    v-model:sort-by="options.sortBy"
    v-model:page="options.page"
    :headers="headers"
    :items="collections"
    :items-length="total"
    :loading="loading"
    item-value="id"
    @update:options="fetchCollections"
  >
    <template #[`item.issue_date`]="{ item }">
      {{ formatDate(item.issue_date) }}
    </template>

    <template #[`item.id`]="{ item }">
      <RouterLink :to="`/collections/${item.id}`" class="text-primary font-weight-bold text-decoration-none">
        {{ formatModelId(item.id, 'COB') }}
      </RouterLink>
    </template>

    <template #[`item.period`]="{ item }">
      <span v-if="item.period">
        {{ formatPeriod(item.period) }}
      </span>
      <span v-else class="text-grey">Manual</span>
    </template>

    <template #[`item.client`]="{ item }">
      <span v-if="item.client">{{ item.client?.last_name }} {{ item.client?.name }}</span>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.contract`]="{ item }">
      <span v-if="item.contract">{{ formatModelId(item.contract.id, 'CON') }}</span>
      <span v-else class="text-grey">—</span>
    </template>

    <template #[`item.total_amount`]="{ item }">
      {{ formatMoney(item.total_amount, item.currency) }}
    </template>

    <template #[`item.status`]="{ item }">
      <v-chip size="small" :color="statusColor(item.status)" variant="flat">
        {{ formatStatus(item.status) }}
      </v-chip>
    </template>
  </v-data-table-server>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from '@/services/axios'
import { formatModelId } from '@/utils/models-formatter'
import { formatMoney } from '@/utils/money'
import { formatPeriod, formatStatus, statusColor } from '@/utils/collections-formatter'
import { formatDate } from '@/utils/date-formatter';
const emit = defineEmits(['error'])

const collections = ref([])
const total = ref(0)
const loading = ref(false)

const options = reactive({
  page: 1,
  itemsPerPage: 10,
  sortBy: [{ key: 'id', order: 'desc' }],
})

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
})

watch(() => props.filters, () => {
  options.page = 1
  fetchCollections()
}, { deep: true })

const headers = [

  { title: 'Cobranza', key: 'id', sortable: true },
  { title: 'Cliente', key: 'client', sortable: false },
  { title: 'Contrato', key: 'contract', sortable: false },
  { title: 'Importe', key: 'total_amount', sortable: true },
  { title: 'Período', key: 'period', sortable: true },
  { title: 'Fecha', key: 'issue_date', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
]

const fetchCollections = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/collections', {
      params: {
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_direction: options.sortBy[0]?.order,
        search: props.filters
      },
    })
    collections.value = data.data
    total.value = data.total
  } catch (error) {
    console.log(error)
    emit('error', 'No se pudieron cargar las cobranzas.')
  } finally {
    loading.value = false
  }
}

defineExpose({ reload: fetchCollections })
</script>

<style scoped>
.text-decoration-none {
  text-decoration: none;
}
</style>
