// stores/notifications.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSocketNotificationsStore = defineStore('notifications', () => {
  const visitNewQueue = ref([]) // Cola para visit-new
  const visitCallQueue = ref([]) // Cola para visit-call

  // Agregar notificaciones a las colas
  const enqueueVisitNew = (notification) => {
    visitNewQueue.value.push(notification)
  }

  const enqueueVisitCall = (notification) => {
    visitCallQueue.value.push(notification)
  }
  const removeNotification = () => {
    visitCallQueue.value.shift()
  }

  // Obtener y remover la próxima notificación de la cola
  const dequeueVisitNew = () => visitNewQueue.value.shift()
  const dequeueVisitCall = () => visitCallQueue.value.shift()

  return {
    visitNewQueue,
    visitCallQueue,
    enqueueVisitNew,
    enqueueVisitCall,
    dequeueVisitNew,
    dequeueVisitCall,
    removeNotification,
  }
})
