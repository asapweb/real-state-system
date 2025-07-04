<template>
  <v-card title="das" v-if="appointment">
    <template v-slot:title>
      <span class="text-capitalize"
        >{{ appointment.client.name }}
        {{ appointment.client.last_name }}
        {{ appointment.client.company_name }}
        <v-btn text="Editar" variant="plain" @click="showFormDialog()"></v-btn>
      </span>
    </template>
    <v-card-text
      ><v-row dense
        ><v-col>
          <v-table density="compact">
            <tbody>
              <tr>
                <td class="text-grey-darken-1">Departamento</td>
                <td>{{ appointment.department.name }}</td>
              </tr>
              <tr v-if="appointment?.employee">
                <td class="text-grey-darken-1">Staff</td>
                <td>
                  {{ appointment.employee.name }}
                </td>
              </tr>
              <tr v-if="appointment?.notes">
                <td class="text-grey-darken-1" style="vertical-align: top">Notas</td>
                <td style="white-space: pre-wrap">
                  {{ appointment.notes }}
                </td>
              </tr>
              <tr>
                <td class="text-grey-darken-1">Estado</td>
                <td>{{ appointment.status }}</td>
              </tr>
              <tr>
                <td class="text-grey-darken-1">Creado</td>
                <td>
                  {{ formatReducedDateTime(appointment.created_at) }}
                  <small> por {{ appointment.creator.name }}</small>
                </td>
              </tr>
              <tr v-if="appointment.received_at">
                <td class="text-grey-darken-1">Recepcionado</td>
                <td>
                  <div>
                    {{ formatReducedDateTime(appointment.received_at) }}
                    <small> por {{ appointment.receiver.name }}</small>
                  </div>
                </td>
              </tr>
              <tr v-if="appointment.received_at">
                <td class="text-grey-darken-1">Tiempo de espera</td>
                <td>
                  <div>
                    {{ getWaitTime(appointment) }}
                  </div>
                </td>
              </tr>
              <tr v-if="appointment.attended_start_at">
                <td class="text-grey-darken-1">Inicio de atención</td>
                <td>
                  <div>
                    {{ formatReducedDateTime(appointment.attended_start_at) }}
                    <small> por {{ appointment.attendant_start.name }}</small>
                  </div>
                </td>
              </tr>
              <tr v-if="appointment.attended_start_at">
                <td class="text-grey-darken-1">Tiempo de atención</td>
                <td>
                  <div>
                    {{ getAttendedTime(appointment) }}
                  </div>
                </td>
              </tr>
              <tr v-if="appointment.attended_end_at">
                <td class="text-grey-darken-1">Fin de atención</td>
                <td>
                  <div>
                    {{ formatReducedDateTime(appointment.attended_end_at) }}
                    <small> por {{ appointment.attendant_end.name }}</small>
                  </div>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions>
      <v-spacer></v-spacer>
      <v-btn
        class="bg-amber"
        v-if="appointment.status === 'arrived'"
        prepend-icon="mdi-bell-outline"
        text="Llamar"
        size="small"
        density="default"
        @click.stop="callClient(appointment)"
      ></v-btn>

      <v-btn
        class="bg-green"
        v-if="appointment.status === 'arrived'"
        prepend-icon="mdi-check"
        text="Atender"
        size="small"
        density="default"
        @click.stop="changeAppointmentStatus(appointment, 'attended')"
      ></v-btn>
      <v-btn
        class="bg-green"
        v-if="appointment.status === 'attended'"
        prepend-icon="mdi-check"
        text="Finalizar"
        size="small"
        density="default"
        @click.stop="changeAppointmentStatus(appointment, 'seen')"
      ></v-btn>
      <v-btn
        class="bg-red"
        prepend-icon="mdi-delete-forever"
        text="Eliminar"
        size="small"
        density="default"
        @click.stop="deleteDialog = true"
      ></v-btn>
    </v-card-actions>
  </v-card>
  <v-dialog v-model="formDialog" v-if="appointment">
    <appointment-form
      :appointmentId="appointment.id"
      @saved="onSaved"
      @close="formDialog = false"
    />
  </v-dialog>
  <v-dialog v-model="deleteDialog" max-width="290">
    <v-card>
      <v-card-title class="headline">Confirmar eliminación</v-card-title>
      <v-card-text> ¿Está seguro que desea eliminar este registro? </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="deleteDialog = false"> Cancelar </v-btn>
        <v-btn color="red darken-1" text @click="deleteAppointment"> Eliminar </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { formatReducedDateTime } from '@/utils/date-formatter'
import axios from '@/services/axios'
import { ref, reactive, onMounted } from 'vue'
import moment from 'moment'
import 'moment/locale/es'
import { sendRequest } from '@/functions'
import appointmentForm from '@/components/AppointmentForm.vue'
const emit = defineEmits(['deleted', 'close'])
const formDialog = ref(false)
const deleteDialog = ref(false)
const props = defineProps(['appointmentId'])

const appointment = ref(null)
const showFormDialog = () => {
  formDialog.value = true
}
const isSubmitting = reactive({})
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
const deleteAppointment = async () => {
  let res = await sendRequest('DELETE', '', 'api/appointments/' + props.appointmentId, '', '')
  if (res) {
    deleteDialog.value = false
    console.log('show emite delete')
    emit('deleted')
  }
}
const changeAppointmentStatus = async (anAppointment, aStatus) => {
  isSubmitting[anAppointment.id]
  await axios
    .post('/api/appointments/' + anAppointment.id + '/status', {
      status: aStatus,
    })
    .then((response) => {
      console.log(response.data)
      emit('close')
    })
    .catch((error) => {
      console.error(error)
    })
}

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
const onSaved = () => {
  loadAppointment(props.appointmentId)
  formDialog.value = false
}

const loadAppointment = async (appointmentId) => {
  try {
    const response = await axios.get('/api/appointments/' + appointmentId, {})
    appointment.value = response.data.data
  } catch (error) {
    console.log('Appointment / loadAppointment error ')
    console.log(error)
  }
}

onMounted(async () => {
  if (props.appointmentId) {
    await loadAppointment(props.appointmentId)
  }
})
</script>
