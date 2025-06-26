<template>
  <div>
    <v-card class="mb-6">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Cobranza {{ formatModelId(collection?.id, 'COB') }}</span>
        <v-chip :color="statusColor(collection?.status)" class="text-white">
          {{ formatStatus(collection?.status) }}
        </v-chip>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <strong>Periodo:</strong> {{ formatPeriod(collection?.period) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Cliente:</strong> {{ collection?.client?.name }}
            {{ collection?.client?.last_name }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Fecha de emisión:</strong> {{ formatDate(collection?.issue_date) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Vencimiento:</strong> {{ formatDate(collection?.due_date) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Contrato:</strong> {{ formatModelId(collection?.contract_id, 'CON') }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Importe total:</strong>
            {{ formatMoney(collection?.total_amount, collection?.currency) }}
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card>
      <v-card-title>Detalle de Items</v-card-title>
      <v-data-table :headers="headers" :items="collection?.items || []" class="elevation-1">
        <template #[`item.amount`]="{ item }">
          {{ formatMoney(item.amount, item.currency) }}
        </template>
        <template #[`item.unit_price`]="{ item }">
          {{ formatMoney(item.unit_price, item.currency) }}
        </template>
        <template #[`item.meta`]="{ item }">
          <v-btn
            v-if="Object.keys(item.meta || {}).length"
            icon="mdi-information-outline"
            variant="text"
            density="compact"
            @click="showMeta(item.meta)"
          ></v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="metaDialog.show" max-width="600px">
      <v-card>
        <v-card-title>Detalle adicional</v-card-title>
        <v-card-text>
          <pre class="text-caption">{{ JSON.stringify(metaDialog.data, null, 2) }}</pre>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="metaDialog.show = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '@/services/axios'
import { useRoute } from 'vue-router'
import { formatModelId } from '@/utils/models-formatter'
import { formatDate } from '@/utils/date-formatter'
import { formatMoney } from '@/utils/money'
import { formatPeriod, formatStatus, statusColor } from '@/utils/collections-formatter'

const route = useRoute()
const collection = ref(null)

const headers = [
  { title: 'Tipo', key: 'type' },
  { title: 'Descripción', key: 'description' },
  { title: 'Cantidad', key: 'quantity' },
  { title: 'Precio unitario', key: 'unit_price' },
  { title: 'Importe', key: 'amount' },
  { title: 'Meta', key: 'meta', sortable: false, align: 'center' },
]

const metaDialog = reactive({
  show: false,
  data: {},
})

const showMeta = (meta) => {
  metaDialog.data = meta
  metaDialog.show = true
}

onMounted(async () => {
  const response = await axios.get(`/api/collections/${route.params.id}`)
  collection.value = response.data
})
</script>
