<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="$router.push('/settings')"
    >
      <svg
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M15 18L9 12L15 6"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </v-btn>
    <h2 class="">Roles y Permisos</h2>
  </div>

  <v-row>
    <v-col md="6" cols="12">
      <v-list lines="one">
        <v-list-item
          v-for="role in availableRoles"
          :key="role.id"
          :to="`/settings/roles/${role.id}`"
          :title="role.name"
          :subtitle="role.description"
          append-icon="mdi-chevron-right"
        >
        </v-list-item>
      </v-list>
    </v-col>
  </v-row>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, onMounted } from 'vue'

const availableRoles = ref([])
const loadingRoles = ref(false)
const errorRoles = ref(false)

const fetchRoles = async () => {
  loadingRoles.value = true
  errorRoles.value = false
  try {
    const response = await axios.get('/api/roles')
    availableRoles.value = response.data.data // Accedemos al array de roles dentro de 'data.data'
  } catch (error) {
    console.error('Error al cargar los roles:', error)
    errorRoles.value = true
  } finally {
    loadingRoles.value = false
  }
}

onMounted(async () => {
  await fetchRoles()
})
</script>
