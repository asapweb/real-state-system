// import './assets/main.css'

import { createApp, markRaw } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

// Vuetify
import vuetify from './plugins/vuetify';
import 'vuetify/styles'

import App from './App.vue'
import router from './router'
import axios from './services/axios'

import '@mdi/font/css/materialdesignicons.css'

import { initializeNotificationsListener } from './listeners/notificationsListener'


const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

pinia.use(({ store }) => {
  store.router = markRaw(router)
})

const app = createApp(App)
app.use(pinia)
app.use(router)
app.use(vuetify)

initializeNotificationsListener() // Inicia el listener para notificaciones

app.mount('#app')
