<template>
  <v-container>
    <h1>Register</h1>
    <v-form @submit.prevent="($event) => authStore.register(form)">
      <v-text-field label="Name" v-model="form.name" type="name"></v-text-field>
      <v-text-field label="Email" v-model="form.email" type="email"></v-text-field>
      <v-text-field label="Password" v-model="form.password" type="password"></v-text-field>
      <v-btn color="primary" type="submit">Enviar</v-btn>
    </v-form>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const form = ref({ name: '', email: '', password: '' })
</script>
