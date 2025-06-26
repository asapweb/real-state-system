<script setup>
import { ref, watch } from 'vue'
import { useSocketNotificationsStore } from '../stores/socketNotifications'

const store = useSocketNotificationsStore()
const showDialog = ref(false)
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
  // currentMessage.value = store.visitCallQueue[0]
  showDialog.value = true

  setTimeout(() => {
    showDialog.value = false
    setTimeout(() => {
      showNextNotification()
    }, 500)
  }, 5000)
}
</script>

<template>
  <v-dialog v-model="showDialog" max-width="400">
    <v-card color="black" class="pa-7">
      <v-card-text class="text-center">
        <h1 style="font-size: 4em">{{ currentMessage?.client }}</h1>
        <h2 style="font-size: 2.5em; font-weight: 300">{{ currentMessage?.department }}</h2>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
