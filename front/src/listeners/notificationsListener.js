// listeners/notificationsListener.js
import echo from '../services/sockets'
import { useSocketNotificationsStore } from '../stores/socketNotifications'

export function initializeNotificationsListener() {
  const notificationsStore = useSocketNotificationsStore()

  echo.channel('visits').listen('.visit-call', (data) => {
    console.log('listener')
    console.log(data)
    console.log('------------ listener visit-call')
    notificationsStore.enqueueVisitCall(data) // Encola la notificación en el store
  })
  echo.channel('visits').listen('.visit-new', (data) => {
    console.log('listener visit-new')
    console.log(data)
    console.log('------------ listener')
    notificationsStore.enqueueVisitNew(data) // Encola la notificación en el store
  })
  echo.channel('visits').listen('.visit-status', (data) => {
    // console.log('listener visit-status')
    // console.log(data.appointment.client.name + ' ' + data.appointment.client.last_name)
    // console.log(data.socketId)
    // console.log(echo.socketId())
    // console.log(echo.socketId() === data.socketId)
    // console.log(echo.socketId() == data.socketId)
    console.log('------------ listener visit-status')
    console.log(data)
    notificationsStore.enqueueVisitNew(data) // Encola la notificación en el store
  })
}
