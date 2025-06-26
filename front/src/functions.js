import { useNotificationStore } from '@/stores/notification'
import axios from './services/axios'

export async function sendRequest(method, params, url, redirect = '') {
  let res
  await axios({ method: method, url: url, data: params })
    .then((response) => {
      res = response
      setTimeout(() => (redirect !== '' ? (window.location.href = redirect) : ''), 2000)
    })
    .catch((errors) => {
      res = errors
      const notificationStore = useNotificationStore()
      notificationStore.showNotification({
        text: errors.response?.data?.message || 'Ocurrió un error inesperado.',
        color: 'error',
      })

      // let desc = ''
      // // res = errors.response.data.status
      // errors.response.data.errors.map((e) => {
      //   desc = desc + ' ' + e
      // })
      // console.log(desc)
    })
  return res
}

export function confirmation(name, url, redirect = '') {
  if (confirm('Confirma eliminar ' + name)) {
    sendRequest('DELETE', '', url, redirect)
  }
}
