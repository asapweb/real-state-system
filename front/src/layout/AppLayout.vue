<script setup>
import GlobalSnackbar from '@/components/GlobalSnackbar.vue'
import SocketNewNotifications from '@/components/SocketNewNotifications.vue'
import LayoutSideNav from './LayoutSideNav.vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import { version } from '../../package.json'
import { sendRequest } from '@/functions'

const authStore = useAuthStore()
const router = useRouter()

const api_version = ref('cargando...')
onMounted(async () => {
  try {
    const res = await sendRequest('get', {}, '/api/version', '')
    api_version.value = res.data.api_version
  } catch (error) {
    console.error('No se pudo obtener la versión de la API:', error)
    api_version.value = 'Error'
  }
})

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Error during logout:', error)
    // Opcional: mostrar un snackbar de error al usuario
  }
}
</script>

<template>
  <SocketNewNotifications />
  <v-app>
    <LayoutSideNav />

    <v-app-bar class="elevation-0">
      <!-- <template #title>
        <div class="font-weight-light">CASSA</div>
      </template> -->

      <v-spacer></v-spacer>

      <v-menu v-if="authStore.isAuthenticated">
        <template #activator="{ props }">
          <v-btn variant="text" v-bind="props">
            {{ authStore.user.name }}
            <v-icon end>mdi-menu-down</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/user" title="Mi Perfil"></v-list-item>
          <v-list-item @click="handleLogout" title="Cerrar sesión"></v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container fluid class="py-8 px-6">
        <RouterView />
        <GlobalSnackbar />
      </v-container>
    </v-main>

    <v-footer app>
      <small><strong>CASSA</strong></small>
      <v-spacer></v-spacer>
      <small>UI: {{ version }} / API: {{ api_version }}</small>
    </v-footer>
  </v-app>
</template>
