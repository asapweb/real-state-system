<template>
  <v-card>
    <v-card-title class="text-h6 font-weight-bold">
      <v-icon :color="form.direction === 'in' ? 'success' : (form.direction === 'out' ? 'error' : 'grey')" class="me-2">
        {{ form.direction === 'in' ? 'mdi-arrow-down-bold' : (form.direction === 'out' ? 'mdi-arrow-up-bold' : 'mdi-swap-vertical') }}
      </v-icon>
      Registrar Movimiento de Caja
    </v-card-title>
    <v-card-text>
      <v-form @submit.prevent="onSubmit">
        <cash-account-autocomplete
          v-model="form.cash_account_id"
          :required="true"
          :disabled="loading"
          :error-messages="v$.cash_account_id.$errors.map(e => e.$message)"
          @update:model-value="onCashAccountChange"
        />
        <v-select
          v-model="form.direction"
          :items="directionOptions"
          label="Dirección"
          :error-messages="v$.direction.$errors.map(e => e.$message)"
          :disabled="loading"
          required
        />
        <v-text-field
          v-model.number="form.amount"
          label="Monto"
          type="number"
          min="0.01"
          step="0.01"
          :error-messages="v$.amount.$errors.map(e => e.$message)"
          :disabled="loading"
          required
        />
        <v-select
          v-model="form.currency"
          :items="currencyOptions"
          label="Moneda"
          :error-messages="v$.currency.$errors.map(e => e.$message)"
          :disabled="loading || !!form.cash_account_id"
          required
        />
        <v-text-field
          v-model="form.date"
          label="Fecha"
          type="date"
          :disabled="loading"
        />
        <v-text-field
          v-model="form.reference"
          label="Referencia"
          :disabled="loading"
        />
        <v-alert v-if="errorMessage" type="error" class="mt-2">{{ errorMessage }}</v-alert>
      </v-form>
    </v-card-text>
    <v-card-actions class="justify-end">
      <v-btn text @click="onCancel" :disabled="loading">Cancelar</v-btn>
      <v-btn color="primary" @click="onSubmit" :loading="loading" :disabled="loading || v$.$invalid">Guardar</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, minValue, helpers } from '@vuelidate/validators';
import { defineProps, defineEmits } from 'vue';
import CashAccountAutocomplete from '@/views/components/CashAccountAutocomplete.vue';
import CashMovementService from '@/services/CashMovementService';
import CashAccountService from '@/services/CashAccountService';

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

const form = reactive({
  cash_account_id: '',
  direction: '',
  amount: '',
  currency: '',
  date: '',
  reference: '',
});

const directionOptions = [
  { title: 'Ingreso', value: 'in' },
  { title: 'Egreso', value: 'out' },
];

const currencyOptions = [
  { title: 'Pesos Argentinos (ARS)', value: 'ARS' },
  { title: 'Dólares Estadounidenses (USD)', value: 'USD' },
];

const errorMessage = ref('');

watch(
  () => props.initialData,
  (val) => {
    if (val) {
      form.cash_account_id = val.cash_account_id || null;
      form.direction = val.direction || '';
      form.amount = val.amount || '';
      form.currency = val.currency || '';
      form.date = val.date || new Date().toISOString().slice(0, 10);
      form.reference = val.reference || '';
    } else {
      form.cash_account_id = null;
      form.direction = '';
      form.amount = '';
      form.currency = '';
      form.date = new Date().toISOString().slice(0, 10);
      form.reference = '';
    }
    errorMessage.value = '';
  },
  { immediate: true }
);

const rules = computed(() => ({
  cash_account_id: { required: helpers.withMessage('La caja es obligatoria', required) },
  direction: { required: helpers.withMessage('La dirección es obligatoria', required) },
  amount: {
    required: helpers.withMessage('El monto es obligatorio', required),
    minValue: helpers.withMessage('El monto debe ser mayor a 0', minValue(0.01)),
  },
  currency: { required: helpers.withMessage('La moneda es obligatoria', required) },
}));

const v$ = useVuelidate(rules, form);

function onCashAccountChange(accountId) {
  // Si se selecciona una cuenta, obtener su moneda y setearla automáticamente
  if (accountId) {
    CashAccountService.getCashAccount(accountId)
      .then(res => {
        const account = res.data.data;
        if (account) {
          form.currency = account.currency;
        }
      })
      .catch(() => {
        // Si no se puede obtener la cuenta, no hacer nada
      });
  }
}

async function onSubmit() {
  v$.value.$touch();
  errorMessage.value = '';
  if (!v$.value.$invalid) {
    try {
       emit('submit', form);
    } catch (e) {
      errorMessage.value = e.response?.data?.message || 'Error al registrar el movimiento';
    }
  }
}

function onCancel() {
  emit('cancel');
}
</script>
