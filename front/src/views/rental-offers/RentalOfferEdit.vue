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
      <v-icon>mdi-arrow-left</v-icon>
    </v-btn>
    <h2 class="">Editar Oferta de Alquiler <span class="font-weight-light">/ {{ formatModelId(offer?.id, 'OFE') }}</span></h2>
  </div>

  <RentalOfferForm
    v-if="offer"
    :initial-data="offer"
    :loading="isSubmitting"
    @submit="update"
    @cancel="goBack"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/services/axios';
import RentalOfferForm from './components/RentalOfferForm.vue';
import { useSnackbar } from '@/composables/useSnackbar';
import { formatModelId } from '@/utils/models-formatter';

const route = useRoute();
const router = useRouter();
const snackbar = useSnackbar();

const offer = ref(null);
const isSubmitting = ref(false);
const id = route.params.id;

const goBack = () => router.push('/rental-offers');

const fetchOffer = async () => {
  try {
    const { data } = await axios.get(`/api/rental-offers/${id}`);
    offer.value = data;
  } catch (error) {
    snackbar.error('No se pudo cargar la oferta');
    console.error(error);
    goBack();
  }
};

const update = async (formData) => {
  isSubmitting.value = true;
  try {
    await axios.put(`/api/rental-offers/${id}`, formData);
    router.push('/rental-offers');
  } catch (error) {
    let message = 'Hubo un error al actualizar la oferta';
    if (error.response?.data?.message) message = error.response.data.message;
    snackbar.error(message);
    console.error(error);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(fetchOffer);
</script>
