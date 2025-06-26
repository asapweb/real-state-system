<template>
  <v-snackbar v-model="snackbar.show" :timeout="snackbar.timeout" :color="snackbar.color">
    {{ snackbar.text }}
    <template #actions>
      <v-btn color="white" text @click="hideNotification">Cerrar</v-btn>
    </template>
  </v-snackbar>
</template>

<script>
import { useNotificationStore } from '@/stores/notification'
import { storeToRefs } from 'pinia'

export default {
  setup() {
    const notificationStore = useNotificationStore()
    const { snackbar } = storeToRefs(notificationStore)
    const { hideNotification } = notificationStore

    return {
      snackbar,
      hideNotification,
    }
  },
}
</script>
