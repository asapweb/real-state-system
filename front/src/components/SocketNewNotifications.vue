<script setup>
import { ref, watch, onMounted } from 'vue'
import { useSocketNotificationsStore } from '../stores/socketNotifications'
import { useAuthStore } from '@/stores/auth'
import echo from '../services/sockets'

const authStore = useAuthStore()

const store = useSocketNotificationsStore()
const showDialog = ref(false)
const audio = ref(null)
const currentNotification = ref('')

watch(store.visitNewQueue, (newNotifications) => {
  if (!showDialog.value && newNotifications.length > 0) {
    showNextNotification()
  }
})

const showNextNotification = () => {
  if (store.visitNewQueue.length === 0) {
    showDialog.value = false
    return
  }
  currentNotification.value = store.dequeueVisitNew().data

  const userInDepartment = authStore.authUser.departments.some(
    (objeto) => objeto.id === currentNotification.value.appointment.department_id,
  )
  console.log(userInDepartment)
  console.log(currentNotification.value?.socketId)
  console.log(echo.socketId())
  console.log(currentNotification.value?.socketId === echo.socketId())
  if (!userInDepartment || currentNotification.value?.socketId === echo.socketId()) {
    return
  }
  showDialog.value = true
  // Reproduce el sonido
  if (audio.value) {
    audio.value.play().catch((error) => {
      console.error('Error al reproducir el sonido:', error)
      // Puedes implementar un fallback aquí, como mostrar una notificación visual.
    })
  }

  setTimeout(() => {
    showDialog.value = false
    setTimeout(() => {
      showNextNotification()
    }, 500)
  }, 5000)
}

onMounted(() => {
  // Carga el sonido una sola vez al montar el componente
  audio.value = new Audio('/sounds/notification_ring.mp3') // Ajusta la ruta al archivo de sonido
  audio.value.preload = 'auto' // Precarga el audio para una reproducción más rápida
})
</script>

<template>
  <v-snackbar :timeout="30000" v-model:model-value="showDialog">
    <div class="text-subtitle-1 text-uppercase font-weight-black">
      {{ currentNotification?.appointment.department.name }}
    </div>
    <div class="text-subtitle-2 font-weight-bold">
      {{ currentNotification?.appointment.client.name }}
      {{ currentNotification?.appointment.client.last_name }}
      {{ currentNotification?.appointment.client.company_name }}
      <span v-if="currentNotification?.appointment.status === 'arrived'" class="font-weight-medium"
        >ingresó a <strong>Sala de espera</strong>
        <small>
          por <strong>{{ currentNotification?.appointment.updator.name }}</strong></small
        ></span
      >
      <span v-if="currentNotification?.appointment.status === 'attended'" class="font-weight-medium"
        >esta siendo atendido
        <small
          >por <strong>{{ currentNotification?.appointment.updator.name }}</strong></small
        ></span
      >
      <span v-if="currentNotification?.appointment.status === 'deleted'" class="font-weight-medium"
        >ha sido eliminado
        <small
          >por <strong>{{ currentNotification?.appointment.updator.name }}</strong></small
        ></span
      >
      <span v-if="currentNotification?.appointment.status === 'seen'" class="font-weight-medium"
        >ha finalizado su visita
        <small>
          por <strong>{{ currentNotification?.appointment.updator.name }}</strong></small
        ></span
      >
    </div>
  </v-snackbar>
</template>
