<template>
  <v-dialog v-model="modelValue" max-width="600px">
    <v-card>
      <v-card-title class="text-h6">
        {{ title }}
      </v-card-title>

      <v-card-text>
        <v-form @submit.prevent="submitForm" ref="formRef">
          <v-text-field
            v-model="form.value"
            label="Valor del ajuste"
            type="number"
            min="0"
            step="0.01"
            :rules="[rules.requiredIfApplicable]"
            prepend-inner-icon="mdi-currency-usd"
          />

          <v-textarea
            v-model="form.notes"
            label="Observaciones"
            auto-grow
            rows="2"
            prepend-inner-icon="mdi-note-text"
          />
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="primary" @click="submitForm" :loading="loading">
          Guardar
        </v-btn>
      </v-card-actions>
      <v-divider class="my-2" />
      <v-card-text class="text-caption text-medium-emphasis">
        ¿Necesitás modificar más datos del ajuste?
        <RouterLink
          :to="`/contracts/${adjustment.contract_id}#adjustments`"
          class="ml-1 text-primary text-decoration-none font-weight-medium"
        >
          Ir al contrato
        </RouterLink>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from '@/services/axios';

const emit = defineEmits(['update:modelValue', 'updated', 'error']);

const props = defineProps({
  modelValue: Boolean,
  adjustment: Object,
});

const modelValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});

const formRef = ref(null);
const loading = ref(false);

const form = ref({
  value: null,
  notes: '',
});

const rules = {
  requiredIfApplicable: (v) => {
    if (props.adjustment?.type !== 'index' && (v === null || v === '')) {
      return 'Este campo es obligatorio';
    }
    return true;
  },
};

const title = computed(() =>
  props.adjustment?.value === null
    ? 'Cargar valor del ajuste'
    : 'Editar valor del ajuste'
);

watch(
  () => props.adjustment,
  (adj) => {
    if (adj) {
      form.value.value = adj.value;
      form.value.notes = adj.notes || '';
    }
  },
  { immediate: true }
);

const submitForm = async () => {
  const isValid = await formRef.value?.validate();
  if (!isValid) return;

  loading.value = true;
  try {
    await axios.patch(
      `api/contracts/${props.adjustment.contract_id}/adjustments/${props.adjustment.id}/value`,
      form.value
    );
    emit('updated');
    close();
  } catch (e) {
    console.error(e);
    emit('error', 'No se pudo guardar el ajuste');
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('update:modelValue', false);
};
</script>
