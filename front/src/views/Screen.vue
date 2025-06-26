<script setup>
import { ref, watch, onMounted } from 'vue'
import { useSocketNotificationsStore } from '../stores/socketNotifications'

const store = useSocketNotificationsStore()
const showDialog = ref(false)
const audio = ref(null)
const currentMessage = ref('')

watch(store.visitCallQueue, (newNotifications) => {
  if (!showDialog.value && newNotifications.length > 0) {
    showNextNotification()
  }
})

const showNextNotification = () => {
  if (store.visitCallQueue.length === 0) {
    showDialog.value = false
    return
  }

  currentMessage.value = store.dequeueVisitCall()
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
  audio.value = new Audio('/sounds/waiting_room.mp3') // Ajusta la ruta al archivo de sonido
  audio.value.preload = 'auto' // Precarga el audio para una reproducción más rápida
})
</script>

<template>
  <v-container fluid class="pa-0">
    <div id="video">
      <video ref="videoElement" class="fullscreen-video" controls autoplay loop muted playsinline>
        <source src="/videos/casa_recorrido.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </div>
    <v-dialog v-model="showDialog" width="50%">
      <v-card color="black" class="pa-7">
        <v-card-text class="text-center">
          <h1 style="font-size: 5em">{{ currentMessage?.client }}</h1>
          <h2 style="font-size: 3.5em; font-weight: 300">{{ currentMessage?.department }}</h2>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>
<style>
html {
  background: url(/media/img/bg.0001.jpg) no-repeat center center fixed;
  background-size: cover;
  height: 100%;
  overflow: hidden;
  font-size: 12px;
}

#video {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}
#video video {
  position: absolute;
  z-index: -1;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
