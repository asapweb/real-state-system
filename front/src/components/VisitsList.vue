<script setup>
import axios from '@/services/axios'
import { ref, watch, reactive, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import AppointmentForm from '@/components/AppointmentForm.vue'
import AppointmentShow from '@/components/AppointmentShow.vue'
// import moment from 'moment-timezone'
import moment from 'moment'
import 'moment/locale/es'
import echo from '../services/sockets'
import { formatReducedDateTime } from '@/utils/date-formatter';
echo.channel('visits').listen('.visit-status', () => {
  fetchAppointments()
})

// moment.tz.setDefault('America/Argentina/Buenos_Aires')
moment.locale('es')

// const props = defineProps(['status', 'title', 'filters'])
const props = defineProps({
  status: {
    default: null,
  },
  title: {
    default: '',
  },
  filters: {
    type: Object,
    default: () => reactive({}),
  },
})

const appointments = ref([])
const tablePage = ref(1)
const loading = ref(false)
const totalItems = ref(0)

const perPage = ref(50)
const totalPages = ref(0)
const page = ref(1)

const appointmentDialog = ref(false)

const isSubmitting = reactive({})
const options = ref({})

const formDialog = ref(false)
const usuarioSeleccionado = ref(null)

watch(
  [props.filters],
  () => {
    debouncedFetchAppointments()
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

const debouncedFetchAppointments = debounce(() => {
  fetchAppointments() // Reinicia siempre en la página 1 al filtrar
}, 500)

const fetchAppointments = async () => {
  loading.value = true
  await axios
    .get('/api/appointments', {
      params: {
        page: page.value,
        itemsPerPage: perPage.value,
        // sortBy: opt.sortBy,
        search: {
          status: props.status,
          isDashboard: props.filters?.isDashboard,
          text: props.filters?.text,
          department_id: props.filters?.department_id,
        },
      },
    })
    .then((response) => {
      appointments.value = response.data.data

      // appointments.value = response.data.data
      totalItems.value = response.data.total
      totalPages.value = response.data.last_page
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}

const onAppointmentDelete = () => {
  console.log('padre recibe delete')
  console.log(appointmentDialog.value)
  appointmentDialog.value = false
  console.log(appointmentDialog.value)
  fetchAppointments()
}

onMounted(() => {
  fetchAppointments()
})

const callClient = async (anAppointment) => {
  await axios
    .post('/api/appointments/notifications/call', {
      appointment_id: anAppointment.id,
    })
    .then((response) => {
      console.log(response.data)
    })
    .catch((error) => {
      console.error(error)
    })
}

const changeAppointmentStatus = async (anAppointment, aStatus) => {
  isSubmitting[anAppointment.id]
  await axios
    .post('/api/appointments/' + anAppointment.id + '/status', {
      status: aStatus,
    })
    .then((response) => {
      console.log(response.data)
      fetchAppointments()
    })
    .catch((error) => {
      console.error(error)
    })
}

const showFormDialog = (anAppointment) => {
  usuarioSeleccionado.value = anAppointment === null ? null : anAppointment?.id
  formDialog.value = true
}

const showAppointmentDialog = (anAppointment) => {
  usuarioSeleccionado.value = anAppointment?.id
  appointmentDialog.value = true
}

const onSaved = () => {
  fetchAppointments()
  formDialog.value = false
}
</script>

<template>
  <v-card class="bg-grey-lighten-4 elevation-0 rounded-lg" height="100%">
    <v-dialog v-model="formDialog">
      <appointment-form
        :appointment-id="usuarioSeleccionado"
        :status="props.status"
        @saved="onSaved"
        @close="formDialog = false"
      />
    </v-dialog>
    <v-card-title class="d-flex align-center justify-space-between text-h5">
      <div class="">{{ props.title }}</div>
      <v-btn
        icon="mdi-plus"
        size="large"
        variant="text"
        density="compact"
        @click.stop="showFormDialog(null)"
      ></v-btn>
    </v-card-title>
    <v-card-subtitle class="text-caption">
      {{ totalItems === 1 ? '1 cliente' : totalItems + ' clientes' }}
      <v-progress-circular
        v-if="loading"
        :indeterminate="loading"
        :width="1"
        :size="15"
        color="blue"
      ></v-progress-circular>
    </v-card-subtitle>
    <v-card-text>
      <v-data-iterator
        :items="appointments"
        :page="tablePage"
        @update:options="fetchAppointments"
        :items-per-page="perPage"
      >
        <!-- <template v-slot:loader>
          <v-skeleton-loader class="border" type="article"></v-skeleton-loader>
        </template> -->
        <template v-slot:default="{ items }">
          <template v-for="(item, i) in items" :key="i">
            <v-card
              class="rounded-lg elevation-1 bor mb-2"
              v-bind="item.raw"
              @click="showAppointmentDialog(item.raw)"
            >
              <v-card-text class="d-flex align-start justify-space-between">
                <div @click="drawer = true">
                  <div class="text-body-1 font-weight-medium">
                    <div v-if="item.raw.client.client_type === 'person'">
                      {{ item.raw.client.name }} {{ item.raw.client.last_name }}
                    </div>
                    <div v-else>{{ item.raw.client.company_name }}</div>
                  </div>

                  <div class="text-caption text-medium-emphasis me-1">
                    <div class="text-body-2">{{ item.raw?.department.name }}<br /></div>
                    <div class="text-">
                      <div v-if="item.raw.received_at">
                        Recepcionado {{ formatReducedDateTime(item.raw.received_at) }} ({{
                          getWaitTime(item.raw)
                        }}
                        de espera)
                      </div>
                      <div v-if="item.raw.attended_start_at">
                        Inicio de atención
                        {{ formatReducedDateTime(item.raw.attended_start_at) }} ({{
                          getAttendedTime(item.raw)
                        }})
                      </div>
                      <div v-if="item.raw.attended_end_at">
                        Fin de atención
                        {{ formatReducedDateTime(item.raw.attended_end_at) }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex ga-2 justify-end">
                  <v-btn
                    class="bg-amber"
                    v-if="item.raw.status === 'arrived'"
                    icon="mdi-bell-outline"
                    size="small"
                    density="default"
                    @click.stop="callClient(item.raw)"
                  ></v-btn>

                  <v-btn
                    class="bg-green"
                    v-if="item.raw.status === 'arrived'"
                    icon="mdi-check"
                    size="small"
                    density="default"
                    @click.stop="changeAppointmentStatus(item.raw, 'attended')"
                  ></v-btn>
                  <v-btn
                    class="bg-green"
                    v-if="item.raw.status === 'attended'"
                    icon="mdi-check"
                    size="small"
                    density="default"
                    @click.stop="changeAppointmentStatus(item.raw, 'seen')"
                  ></v-btn>
                </div>
              </v-card-text>
            </v-card>
          </template>
          <v-dialog v-model="appointmentDialog" max-width="600">
            <appointment-show
              :appointmentId="usuarioSeleccionado"
              @close="appointmentDialog = false"
              @deleted="onAppointmentDelete"
            />
          </v-dialog>
        </template>

        <template v-slot:footer v-if="totalPages > 1">
          <v-pagination
            v-model="page"
            :length="totalPages"
            @input="fetchAppointments"
            @update:model-value="fetchAppointments"
            v-model:options="options"
          ></v-pagination>
        </template>
      </v-data-iterator>
    </v-card-text>
  </v-card>
</template>
