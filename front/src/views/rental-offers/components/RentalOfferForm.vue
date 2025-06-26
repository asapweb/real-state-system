<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Selección de Propiedad -->
          <v-row>
            <v-col cols="12" md="6">
              <PropertyAutocomplete
                v-model="formData.property_id"
                :error-messages="v$.property_id.$errors.map(e => e.$message)"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Condiciones económicas -->
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.price"
                label="Precio mensual"
                type="number"
                :error-messages="v$.price.$errors.map(e => e.$message)"
                @blur="v$.price.$touch"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="formData.currency"
                :items="currencyOptions"
                item-title="label"
                item-value="value"
                label="Moneda"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.duration_months"
                label="Duración (meses)"
                type="number"
                :error-messages="v$.duration_months.$errors.map(e => e.$message)"
                @blur="v$.duration_months.$touch"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.common_expenses_amount"
                label="Expensas estimadas"
                type="number"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.availability_date"
                label="Disponibilidad"
                type="date"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Sellado -->
          <v-row>
            <v-col cols="12" md="2">
              <v-switch
                v-model="formData.seal_required"
                label="Requiere sellado"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.seal_amount"
                label="Monto de sellado"
                type="number"
                :disabled="!formData.seal_required"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="formData.seal_currency"
                :items="currencyOptions"
                item-title="label"
                item-value="value"
                label="Moneda"
                :disabled="!formData.seal_required"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-text-field
                v-model="formData.seal_percentage_owner"
                label="% a cargo propietario"
                type="number"
                :disabled="!formData.seal_required"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.seal_percentage_tenant"
                label="% a cargo inquilino"
                type="number"
                :disabled="!formData.seal_required"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Seguro y depósito -->
          <v-row>
            <v-col cols="12" md="3">
              <v-switch
                v-model="formData.includes_insurance"
                label="Incluye seguro"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="formData.insurance_quote_amount"
                label="Monto estimado"
                type="number"
                :disabled="!formData.includes_insurance"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-switch v-model="formData.allow_pets" label="Admite mascotas" />
            </v-col>
            <v-col cols="12" md="6">
              <v-textarea
                v-model="formData.commission_policy"
                label="Política de comisión"
                rows="2"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-textarea
                v-model="formData.deposit_policy"
                label="Política de depósito"
                rows="2"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Estado y notas -->
          <v-row>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Estado"
                :error-messages="v$.status.$errors.map(e => e.$message)"
                @blur="v$.status.$touch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.published_at"
                label="Fecha de publicación"
                type="datetime-local"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formData.notes" label="Notas internas" rows="3" />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" color="blue-grey" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar Oferta
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, numeric, minValue } from '@vuelidate/validators';
import PropertyAutocomplete from '@/views/components/PropertyAutocomplete.vue';

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['submit', 'cancel']);

const formData = reactive({
  property_id: null,
  price: null,
  currency: 'ARS',
  duration_months: null,
  availability_date: null,
  common_expenses_amount: null,
  seal_required: false,
  seal_amount: null,
  seal_currency: 'ARS',
  seal_percentage_owner: null,
  seal_percentage_tenant: null,
  includes_insurance: false,
  insurance_quote_amount: null,
  commission_policy: '',
  deposit_policy: '',
  allow_pets: false,
  status: 'draft',
  published_at: null,
  notes: ''
});

const rules = {
  property_id: { required },
  price: { required, numeric, minValue: 0 },
  duration_months: { required, numeric, minValue: 1 },
  status: { required }
};

const v$ = useVuelidate(rules, formData);
const formRef = ref(null);

const statusOptions = [
  { value: 'draft', label: 'Borrador' },
  { value: 'published', label: 'Publicado' },
  { value: 'paused', label: 'Pausado' },
  { value: 'closed', label: 'Cerrado' }
];

const currencyOptions = [
  { value: 'ARS', label: 'Pesos (ARS)' },
  { value: 'USD', label: 'Dólares (USD)' },
  { value: 'EUR', label: 'Euros (EUR)' }
];

const handleSubmit = async () => {
  const isValid = await v$.value.$validate();
  if (!isValid) return;
  emit('submit', { ...formData });
};

onMounted(() => {
  if (props.initialData?.id) {
    Object.assign(formData, props.initialData);
  }
});
</script>
