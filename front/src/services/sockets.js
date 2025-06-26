// SOCKETS
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from './axios'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
})

echo.connector.pusher.connection.bind('connected', () => {
  console.log('Conectado con éxito:', echo.socketId())
  axios.defaults.headers.common['X-Socket-Id'] = echo.socketId()
})

export default echo
