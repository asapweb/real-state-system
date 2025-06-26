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
    <h2 class="">
      Solicitud de Alquiler
      <span class="font-weight-light">/ {{ formatModelId(application?.id, 'SOL') }}</span>
    </h2>
  </div>

  <div>
    <v-row>
      <v-col>
        <RentalApplicationInfoCard v-if="application" :application="application" />
      </v-col>
      <v-col>
        <RentalApplicationClientTable v-if="application" :application-id="application.id" />
      </v-col>
      <v-col>
        <AttachmentManager
          v-if="application"
          :attachable-id="application.id"
          attachable-type="rental-application"
        />
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :timeout="6000" color="red" top>
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/services/axios'
import RentalApplicationInfoCard from './components/RentalApplicationInfoCard.vue'
import RentalApplicationClientTable from './components/RentalApplicationClientTable.vue'
import AttachmentManager from '@/views/components/AttachmentManager.vue'
import { formatModelId } from '@/utils/models-formatter'

const router = useRouter()
const route = useRoute()

const application = ref(null)
const loading = ref(false)
const snackbar = ref({ show: false, message: '' })

const goBack = () => {
  router.push({ name: 'RentalApplicationIndex' })
}

const loadApplication = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/rental-applications/${route.params.id}`)
    application.value = data
  } catch (error) {
    snackbar.value.message = 'No se pudo cargar la solicitud.'
    snackbar.value.show = true
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(loadApplication)
</script>
