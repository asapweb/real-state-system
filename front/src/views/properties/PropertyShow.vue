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
      Propiedad <span class="font-weight-light">/ {{ formatModelId(property?.id, 'PRO') }}</span>
    </h2>
  </div>
  <div>
    <v-tabs v-model="tab" background-color="transparent" grow>
      <v-tab value="info">Información</v-tab>
      <v-tab value="attachments">Adjuntos</v-tab>
      <v-tab value="owners">Propietarios</v-tab>
      <v-tab value="services">Servicios</v-tab>
    </v-tabs>

    <v-window v-model="tab">
      <!-- Info -->
      <v-window-item value="info">
        <PropertyInfoCard v-if="property" :property="property" @edit="goToEdit" />
      </v-window-item>

      <!-- Adjuntos -->
      <v-window-item value="attachments">
        <AttachmentManager
          v-if="property"
          :attachable-type="'property'"
          :attachable-id="property.id"
        />
      </v-window-item>

      <!-- Owners -->
      <v-window-item value="owners">
        <PropertyOwnerTable :property-id="property.id" />
      </v-window-item>

      <!-- Services -->
      <v-window-item value="services">
        <PropertyService :property-id="property.id" />
      </v-window-item>
    </v-window>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import PropertyInfoCard from './components/PropertyInfoCard.vue'
import PropertyOwnerTable from './components/PropertyOwnerTable.vue'
import PropertyService from './components/PropertyService.vue'
import AttachmentManager from '@/views/components/AttachmentManager.vue'
import { formatModelId } from '@/utils/models-formatter'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const tab = ref('info')
const property = ref(null)

const goBack = () => {
  router.push('/properties')
}

const goToEdit = () => {
  router.push(`/properties/${property.value.id}/edit`)
}

const fetchProperty = async () => {
  const { data } = await axios.get(`/api/properties/${route.params.id}`)
  property.value = data.data
}

onMounted(async () => {
  await fetchProperty()
})
</script>
