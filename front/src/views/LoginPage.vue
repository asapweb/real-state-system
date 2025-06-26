<template>
  <v-app>
    <v-snackbar v-model="snackbarShow" :timeout="3000" color="red" location="top">
      {{ snackbarText }}
    </v-snackbar>
    <v-row no-gutters class="fill-height">
      <v-col cols="12" md="7" class="d-none d-md-flex pa-0">
        <v-img class="align-end" cover src="/interior5.jpeg">
          <v-card-title class="text-white">Un espacio diseñado para la eficiencia.</v-card-title>
        </v-img>
      </v-col>

      <v-col cols="12" md="5" class="d-flex align-center justify-center bg-surface">
        <div class="pa-4" style="width: 100%; max-width: 400px">
          <h5 class="text-h5 font-weight-bold mb-2">CASSA | Grupo Inmobiliario</h5>
          <div class="text-subtitle-1 mb-6">Sistema de gestión</div>

          <v-form @submit.prevent="handleLogin">
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              variant="outlined"
              prepend-inner-icon="mdi-email-outline"
              autocomplete="username"
            ></v-text-field>

            <v-text-field
              v-model="form.password"
              label="Password"
              type="password"
              variant="outlined"
              prepend-inner-icon="mdi-lock-outline"
              autocomplete="current-password"
            ></v-text-field>

            <div class="text-right my-2">
              <v-btn variant="text" size="small" color="primary" to="/forgot-password">
                ¿Olvidaste tu contraseña?
              </v-btn>
            </div>

            <v-btn block color="primary" size="large" type="submit" :loading="isLoading">
              Login
            </v-btn>
          </v-form>
        </div>
      </v-col>
    </v-row>
  </v-app>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = useAuthStore()

const snackbarShow = ref(false)
const snackbarText = ref('')
const isLoading = ref(false)
const form = reactive({ email: '', password: '' })

const handleLogin = async () => {
  isLoading.value = true
  try {
    await authStore.login(form)
    router.push('/') // Redirigir al dashboard después del login
  } catch (errors) {
    snackbarShow.value = true
    snackbarText.value = errors.response?.data?.message || 'Email o contraseña incorrectos.'
  } finally {
    isLoading.value = false
  }
}
</script>
