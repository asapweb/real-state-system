<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="goBack"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </v-btn>
    <h2 class="">Cliente <span class="font-weight-light">/ {{ client.name }} {{ client.last_name }}</span></h2>

  </div>
  <VRow>
    <VCol cols="6" class="mb-4">
      <ClientInfoCard :client="client" @edit="goToEdit" />
    </VCol>
    <v-col>
      <AttachmentManager v-if="client" :attachable-type="'client'" :attachable-id="12" />
    </v-col>
  </VRow>

</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import AttachmentManager from '@/views/components/AttachmentManager.vue';
import ClientInfoCard from './components/ClientInfoCard.vue';

const route = useRoute()
const router = useRouter()

const client = ref({})

const fetchClient = async () => {
  try {
    const { data } = await axios.get(`/api/clients/${route.params.id}`)
    client.value = data
  } catch (error) {
    console.error('Error al cargar cliente', error)
    router.push('/clients')
  }
}

const goBack = () => {
  router.push('/clients')
}
const goToEdit = () => {
  router.push(`/clients/${client.value.id}/edit`)
}

onMounted(() => {
  fetchClient()
})
</script>
