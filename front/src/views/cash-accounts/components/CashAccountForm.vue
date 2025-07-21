<template>
  <v-card>
    <v-card-title class="text-h6 font-weight-bold">Formulario de Caja</v-card-title>
    <v-card-text>
      <v-form @submit.prevent="onSubmit">
        <v-text-field
          v-model="form.name"
          label="Nombre de la caja"
          :error-messages="v$.name.$errors.map(e => e.$message)"
          :disabled="loading"
          required
        />
        <v-select
          v-model="form.type"
          :items="typeOptions"
          label="Tipo de caja"
          :error-messages="v$.type.$errors.map(e => e.$message)"
          :disabled="loading"
          required
        />
        <v-select
          v-model="form.currency"
          :items="currencyOptions"
          label="Moneda"
          :error-messages="v$.currency.$errors.map(e => e.$message)"
          :disabled="loading"
          required
        />
        <v-switch
          v-model="form.is_active"
          label="Caja activa"
          color="success"
          :disabled="loading"
        />
      </v-form>
    </v-card-text>
    <v-card-actions class="justify-end">
      <v-btn text @click="onCancel" :disabled="loading">Cancelar</v-btn>
      <v-btn color="primary" @click="onSubmit" :loading="loading" :disabled="loading">Guardar</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers, minLength, maxLength } from '@vuelidate/validators';
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  initialData: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const typeOptions = [
  { title: 'Efectivo', value: 'cash' },
  { title: 'Banco', value: 'bank' },
  { title: 'Virtual', value: 'virtual' },
];
const currencyOptions = [
  { title: 'ARS', value: 'ARS' },
  { title: 'USD', value: 'USD' },
];

const form = reactive({
  name: '',
  type: '',
  currency: '',
  is_active: true,
});

watch(
  () => props.initialData,
  (val) => {
    if (val) {
      form.name = val.name || '';
      form.type = val.type || '';
      form.currency = val.currency || '';
      form.is_active = val.is_active !== undefined ? val.is_active : true;
    }
  },
  { immediate: true }
);

const rules = computed(() => ({
  name: { required: helpers.withMessage('El nombre es obligatorio', required) },
  type: { required: helpers.withMessage('El tipo es obligatorio', required) },
  currency: {
    required: helpers.withMessage('La moneda es obligatoria', required),
    minLength: helpers.withMessage('Debe tener 3 letras', minLength(3)),
    maxLength: helpers.withMessage('Debe tener 3 letras', maxLength(3)),
  },
}));

const v$ = useVuelidate(rules, form);

async function onSubmit() {
  v$.value.$touch();
  if (!v$.value.$invalid) {
    emit('submit', { ...form });
  }
}

function onCancel() {
  emit('cancel');
}
</script>
