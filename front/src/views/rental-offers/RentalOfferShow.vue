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
      <h2 class="">Oferta de Alquiler <span class="font-weight-light">/ {{ formatModelId(offer?.id, 'OFE') }}</span></h2>
    </div>
    <div v-if="offer">

      <v-row>
        <v-col cols="12" md="6">
          <RentalOfferInfoCard :offer="offer" @edit="goToEdit" />
        </v-col>
        <v-col>
          <RentalOfferApplications v-if="offer" :rental-offer-id="offer?.id" />
        </v-col>
        <v-col>
          <AttachmentManager v-if="offer" :attachable-type="'rental_offer'" :attachable-id="offer.id" />
        </v-col>
        <v-col cols="12" md="12">
          <RentalOfferServiceStatusTable :rental-offer-id="offer?.id" />
        </v-col>
      </v-row>
    </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router'
  import axios from '@/services/axios';
  import RentalOfferInfoCard from './components/RentalOfferInfoCard.vue';
  import RentalOfferServiceStatusTable from './components/RentalOfferServiceStatusTable.vue';
  import RentalOfferApplications from './components/RentalOfferApplications.vue';
  import AttachmentManager from '@/views/components/AttachmentManager.vue';
  import { useSnackbar } from '@/composables/useSnackbar';
  import { formatModelId } from '@/utils/models-formatter';

  const route = useRoute()
  const router = useRouter()
  const snackbar = useSnackbar();
  const offer = ref(null);


  const goBack = () => {
    router.push('/rental-offers')
  }

  const goToEdit = () => {
    router.push(`/rental-offers/${offer.value.id}/edit`)
  }

  const fetchOffer = async () => {
    try {
      const { data } = await axios.get(`/api/rental-offers/${route.params.id}`);
      offer.value = data;
    } catch (error) {
      snackbar.error('No se pudo cargar la oferta');
      console.error(error);
    }
  };

  onMounted(fetchOffer);
  </script>
