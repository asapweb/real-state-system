<template>
  <v-container fluid class="pa-0">
    <v-row>
      <v-col cols="12" class="fullscreen-wrapper">
        <video
          ref="videoElement"
          class="fullscreen-video"
          controls
          autoplay
          loop
          muted
          playsinline
          preload="auto"
          disablePictureInPicture
          disableRemotePlayback
        >
          <source src="/videos/casa_recorrido.mp4" type="video/mp4" />
          Your browser does not support the video tag.
        </video>
      </v-col>
    </v-row>
    <button type="button" @click="toggleFullscreen">Fullscreen</button>

    <v-dialog v-model="isModalVisible" width="40%">
      <v-card color="black">
        <v-card-text class="text-center">
          <h1 style="font-size: 4em">{{ currentNotification?.client }}</h1>
          <h2>{{ currentNotification?.department }}</h2>
        </v-card-text>
      </v-card>
    </v-dialog>
    <v-dialog v-model="isModalVisible2" width="40%">
      <v-card color="black">
        <v-card-text class="text-center">
          <h1 style="font-size: 4em">Juan</h1>
          <h2 style="font-size: 2.5em; font-weight: 500">Gestiones de mantenimiento</h2>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { api as fullscreen } from 'vue-fullscreen'
import { useSocketNotificationsStore } from '../stores/socketNotifications'

const socketNotificationsStore = useSocketNotificationsStore()
const currentNotification = ref(null)
let interval = null

const notificationSound = ref(new Audio('sounds/waiting_room.mp3'))
const isModalVisible = ref(false)
const isModalVisible2 = ref(true)

const videoElement = ref(null)
const fullscreenState = ref(false)
const teleport = ref(true)

const startDisplayingNotifications = () => {
  console.log('startDisplayingNotifications')
  console.log(socketNotificationsStore.visitCallQueue)
  // console.log('------------------- startDisplayingNotifications')
  interval = setInterval(() => {
    console.log(' INTERVALO --------------------- --')
    console.log(socketNotificationsStore.visitCallQueue.length)
    isModalVisible.value = false
    // console.log(socketNotificationsStore.visitCallQueue)
    // console.log(socketNotificationsStore.visitCallQueue.length > 0)
    // console.log('--------------------- --')
    if (socketNotificationsStore.visitCallQueue.length > 0) {
      console.log('muestra notificacion')
      console.log(socketNotificationsStore.visitCallQueue)
      console.log('deuqeue')
      const nextNotification = socketNotificationsStore.dequeueVisitCall()
      console.log(socketNotificationsStore.visitCallQueue)
      currentNotification.value = nextNotification
      console.log(currentNotification.value)

      notificationSound.value.play().catch((error) => {
        console.error('Error reproduciendo el sonido:', error)
      })
      isModalVisible.value = true
      console.log('----------------- muestra notificacion')
    } else {
      console.log(' CIERRA EL DIALOGO')
      isModalVisible.value = false
      // clearInterval(interval)
    }
  }, 5000)
}

const toggleFullscreen = async () => {
  const wrapperElement = videoElement.value.closest('.fullscreen-wrapper')
  await fullscreen.toggle(wrapperElement, {
    teleport: teleport.value,
    callback: (isFullscreen) => {
      fullscreenState.value = isFullscreen
    },
  })
}
onMounted(() => {
  startDisplayingNotifications()
})

onBeforeUnmount(() => {
  if (interval) clearInterval(interval)
})
</script>

<style scoped>
.fullscreen-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fullscreen-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
