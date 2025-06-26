// stores/notifications.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useVisitsStore = defineStore('visits', () => {
  const received = ref([]) // Cola para visit-new
  const attended = ref([]) // Cola para visit-call
  const seen = ref([]) // Cola para visit-call

  // Agregar notificaciones a las colas
  const enqueueVisitNew = (notification) => {
    visitNewQueue.value.push(notification)
  }

  const enqueueVisitCall = (notification) => {
    visitCallQueue.value.push(notification)
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
  }
})
