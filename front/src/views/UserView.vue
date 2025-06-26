<script setup>
import { ref, reactive } from 'vue'
import { sendRequest } from '@/functions'

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const error = ref(null)
const success = ref(null)

const cambiarContraseña = async () => {
  error.value = null
  success.value = null

  try {
    const response = await sendRequest('POST', form, `/api/change-password`, '')
  } catch (err) {
    error.value = err.response.data.message || 'Error al cambiar la contraseña'
  }
}
</script>

<template>
  <div class="mt-3 mb-10">
    <h2 class="">Mi perfil</h2>
  </div>

  <v-row>
    <v-col cols="12" sm="8" md="6">
      <v-card class="elevation-1 rounded-lg">
        <v-card-title>Cambiar contraseña</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="cambiarContraseña">
            <v-text-field
              v-model="form.current_password"
              label="Contraseña actual"
              type="password"
            ></v-text-field>
            <v-text-field
              v-model="form.password"
              label="Nueva contraseña"
              type="password"
            ></v-text-field>
            <v-text-field
              v-model="form.password_confirmation"
              label="Confirmar nueva contraseña"
              type="password"
              required
            ></v-text-field>
            <v-btn type="submit" color="primary">Guardar cambios</v-btn>
          </v-form>
          <div v-if="error" class="error--text">{{ error }}</div>
          <div v-if="success" class="success--text">{{ success }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>
