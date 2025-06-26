<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="$router.push('/settings/users')"
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
    <h2 class="">Ver Usuario</h2>
  </div>
  <div v-if="user">
    <v-row>
      <v-col>
        <user-form :client="user" @saved="onSaved" />
      </v-col>
      <v-col> <user-roles :user-id="user.id" /></v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/services/axios'
import userForm from '@/components/UserForm.vue'
import userRoles from '@/views/Settings/Users/Components/UserRoles.vue'

const route = useRoute()
const userId = ref(route.params.id)
const user = ref(null)

const fetchUser = async () => {
  try {
    console.log('fetch')
    console.log(userId.value)
    const response = await axios.get('/api/users/' + userId.value)
    user.value = response.data
  } catch (error) {
    console.error('Error al cargar la información del usuario:', error)
    // Puedes mostrar un mensaje de error al usuario aquí
  }
}

onMounted(async () => {
  console.log(ref(route.params.id))
  console.log(userId.value)
  if (userId.value) {
    await fetchUser()
  }
})

const onSaved = () => {}
</script>
