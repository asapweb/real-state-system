<template>
  <div>
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
      <h2>Generación Mensual de Cobranzas</h2>
    </div>

    <v-card>
      <v-card-text>
        <v-text-field
          v-model="period"
          label="Período (YYYY-MM)"
          placeholder="2025-06"
          :error-messages="periodError"
          @change="validateAndPreview"
        />

        <v-progress-linear v-if="previewLoading" indeterminate class="mt-4" />

        <v-alert
          v-if="preview"
          :type="preview.pending_generation > 0 ? 'warning' : 'info'"
          class="mt-4"
          variant="tonal"
        >
          <div>
            <strong>Período:</strong> {{ preview.period }}<br>
            <strong>Total de contratos activos:</strong> {{ preview.total_contracts }}<br>
            <strong>Ya generados:</strong> {{ preview.already_generated }}<br>
            <strong>Listos para generar:</strong> {{ preview.pending_generation }}<br>
            <strong>Bloqueados por faltantes:</strong> {{ preview.blocked_due_to_missing_months }}
          </div>

          <ul v-if="preview.blocked_contracts.length" class="mt-2 pl-4">
            <li v-for="contract in preview.blocked_contracts" :key="contract.id">
              Contrato {{ contract.id }}{{ contract.name ? ` (${contract.name})` : '' }}: falta {{ contract.missing_period }}
            </li>
          </ul>
        </v-alert>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          :disabled="preview && preview.pending_generation === 0"
          @click="generateCollections"
        >
          Generar
        </v-btn>
      </v-card-actions>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/services/axios';
import { useSnackbar } from '@/composables/useSnackbar';

const router = useRouter();
const goBack = () => router.back();

const period = ref('');
const periodError = ref(null);
const preview = ref(null);
const previewLoading = ref(false);
const snackbar = useSnackbar();

const validateAndPreview = () => {
  const regex = /^\d{4}-\d{2}$/;
  if (!regex.test(period.value)) {
    periodError.value = 'El formato debe ser YYYY-MM';
    preview.value = null;
    return;
  }
  periodError.value = null;
  fetchPreview();
};

const fetchPreview = async () => {
  previewLoading.value = true;
  preview.value = null;

  try {
    const { data } = await axios.post('/api/collections/preview', { period: period.value });
    preview.value = data;
  } catch (error) {
    snackbar.error('No se pudo obtener el estado de cobranzas.');
  } finally {
    previewLoading.value = false;
  }
};

const generateCollections = async () => {
  if (!period.value) return;

  try {
    const { data } = await axios.post('/api/collections/generate', { period: period.value });
    snackbar.success(data.message || 'Cobranzas generadas correctamente');
    setTimeout(() => {
      router.push('/collections');
    }, 1000);
  } catch (error) {
    const response = error?.response?.data;

    if (Array.isArray(response?.errors)) {
      const lines = response.errors.map(
        c => `Contrato ${c.id}${c.name ? ` (${c.name.trim()})` : ''}: falta ${c.period_missing}`
      );
      snackbar.error([response.message ?? 'Error al generar cobranzas', ...lines].join('\n'));
    } else {
      const message = response?.message ?? 'Error al generar cobranzas';
      snackbar.error(message);
    }
  }
};
</script>
