// stores/notificationStore.js
import { defineStore } from 'pinia'

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    snackbar: {
      show: false,
      text: '',
      color: 'error',
      timeout: 3000,
    },
  }),
  actions: {
    showNotification({ text, color = 'error', timeout = 3000 }) {
      this.snackbar = { show: true, text, color, timeout }
    },
    hideNotification() {
      this.snackbar.show = false
    },
  },
})
