<template>
  <div class="mb-8">
    <h2 class="">Visitas</h2>
    <small><a class="text-decoration-none text-primary" href="/">Ver tablero</a></small>
  </div>

  <v-row>
    <v-col class="" cols="12" lg="5" sm="5">
      <v-text-field
        clearable
        variant="solo-filled"
        placeholder="Buscar"
        class="elevation-0"
        hide-details
        v-model="filter.text"
      ></v-text-field>
    </v-col>
    <v-col class="" cols="12" lg="7" sm="7">
      <DepartmentAutocomplete :multiple="true" label="Departemento" v-model="filter.department_id" />
    </v-col>
  </v-row>
  <v-row>
    <v-col>
      <v-data-table-server
        class="mt-0"
        v-model:options="options"
        :headers="headers"
        :items="clientes"
        :items-length="totalItems"
        :items-per-page="25"
        :loading="loading"
        :page="tablePage"
        :sort-by="defaultSort"
        @update:options="fetchClients"
      >
        <template v-slot:loading>
          <v-skeleton-loader type="table-row@4"></v-skeleton-loader>
        </template>
        <template #[`item.client`]="{ item }">
          <div class="font-weight-medium">
            <span v-if="item.client.client_type == 'person'"
              >{{ item.client.name }} {{ item.client.last_name }}</span
            >
            <span v-else>
              {{ item.client.company_name }}
              <small class="text-grey-lighten-1">{{ item.client.business_name }}</small></span
            >
          </div>
        </template>
        <template #[`item.department`]="{ item }">
          <div class="font-weight-medium">
            {{ item.department.name }}
          </div>
        </template>
        <template #[`item.received_at`]="{ item }">
          <div class="font-weight-medium" v-if="item.received_at">
            {{ formatReducedDateTime(item.received_at) }}
            <span class="font-weight-bold">({{ getWaitTime(item) }})</span>
          </div>
        </template>
        <template #[`item.attended_start_at`]="{ item }">
          <div class="font-weight-medium" v-if="item.attended_start_at">
            {{ formatReducedDateTime(item.attended_start_at) }}
            <span class="font-weight-bold">({{ getAttendedTime(item) }})</span>
          </div>
        </template>
        <template #[`item.attended_end_at`]="{ item }">
          <div class="font-weight-medium">
            {{ formatReducedDateTime(item.attended_end_at) }}
          </div>
        </template>
      </v-data-table-server>
    </v-col>
  </v-row>

</template>

<script setup>
import { formatReducedDateTime } from '@/utils/date-formatter'
import axios from '@/services/axios'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { sendRequest } from '@/functions'
import moment from 'moment'
import DepartmentAutocomplete from '@/components/DepartmentAutocomplete.vue'

const defaultSort = ref([{ key: 'received_at', order: 'desc' }])

// Server table
const headers = [
  // { title: 'Id', key: 'id' },
  { title: 'Cliente', key: 'client' },
  { title: 'Departamento', key: 'department' },
  { title: 'Recepción', key: 'received_at' },
  { title: 'Atención', key: 'attended_start_at' },
  { title: 'Completado', key: 'attended_end_at' },
  { title: 'Estado', key: 'status' },
]
const clientes = ref([])
const tablePage = ref(1)
const loading = ref(false)
const totalItems = ref(0)

const search = ref('')
const options = ref({})
const filter = ref({ text: '', department_id: null, status: null })
watch(
  [filter],
  () => {
    loading.value = true
    debouncedFetchClients()
    search.value = String(Date.now())
  },
  { deep: true },
)

const getWaitTime = (appointment) => {
  const now = appointment.attended_start_at ? moment(appointment.attended_start_at) : moment()
  const duration = moment.duration(now.diff(moment(appointment.received_at)))
  const days = duration.days()
  const hours = duration.hours()
  const minutes = duration.minutes()
  return `${days ? days + 'd' : ''} ${hours ? hours + 'h' : ''} ${minutes > 1 ? minutes + 'm' : '1m'}`
}
const getAttendedTime = (appointment) => {
  const now = appointment.attended_end_at ? moment(appointment.attended_end_at) : moment()
  const duration = moment.duration(now.diff(moment(appointment.attended_start_at)))
  const days = duration.days()
  const hours = duration.hours()
  const minutes = duration.minutes()
  return `${days ? days + 'd' : ''} ${hours ? hours + 'h' : ''} ${minutes > 1 ? minutes + 'm' : '1m'}`
}

const debouncedFetchClients = debounce(() => {
  fetchClients() // Reinicia siempre en la página 1 al filtrar
}, 500)

const fetchClients = async () => {
  loading.value = true
  const opt = options.value
  await axios
    .get('/api/appointments', {
      params: {
        page: opt.page,
        itemsPerPage: opt.itemsPerPage,
        sortBy: opt.sortBy,
        search: filter.value,
      },
    })
    .then((response) => {
      clientes.value = response.data.data
      totalItems.value = response.data.total
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}


const onSaved = () => {
  fetchClients()
}
</script>
