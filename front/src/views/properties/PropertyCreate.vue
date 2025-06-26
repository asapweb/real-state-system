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
    <h2>Crear Propiedad</h2>
  </div>

  <PropertyForm @submit="saveProperty" @cancel="goBack" :loading="isSubmitting" />
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PropertyForm from './components/PropertyForm.vue';
import { useSnackbar } from '@/composables/useSnackbar';

const router = useRouter();
const snackbar = useSnackbar();
const isSubmitting = ref(false);

const saveProperty = async (formData) => {
  isSubmitting.value = true;
  try {
    await axios.post('/api/properties', formData);
    router.push('/properties');
  } catch (error) {
    let message = 'Hubo un error al crear la propiedad';

    if (error.response) {
      if (error.response.status === 422 && error.response.data?.message) {
        message = error.response.data.message;
      } else if (error.response.data?.message) {
        message = error.response.data.message;
      } else if (typeof error.response.data === 'string') {
        message = error.response.data;
      }
    }

    snackbar.error(message);
    console.error('Error detalle:', error.response?.data || error);
  } finally {
    isSubmitting.value = false;
  }
};

const goBack = () => {
  router.push('/properties');
};
</script>
