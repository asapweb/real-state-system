<template>
  <v-data-table :items="customers" :headers="headers"> </v-data-table>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
const authStore = useAuthStore()
axios.defaults.headers.common['Authorization'] = 'Bearer ' + authStore.authToken

onMounted(() => {
  getCustomers()
})

const customers = ref([])
const load = ref(false)

const getCustomers = async () => {
  await axios
    .get('/api/departments')
    .then((response) => {
      console.log('1')
      customers.value = response.data
      load.value = true
    })
    .catch((error) => {
      console.error(error)
    })
}

const deleteCustomer = (id, name) => {
  alert(eliminar)
}
</script>
