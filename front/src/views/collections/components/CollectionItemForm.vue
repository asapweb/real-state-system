<template>
  <v-dialog :model-value="dialog" @update:model-value="emit('update:dialog', $event)" max-width="600px">
    <v-card>
      <v-card-title>Agregar / Editar Ítem</v-card-title>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.type"
                :items="typeOptions"
                label="Tipo"
                :error-messages="v$.type.$errors.map(e => e.$message)"
                @blur="v$.type.$touch"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.description"
                label="Descripción"
                :error-messages="v$.description.$errors.map(e => e.$message)"
                @blur="v$.description.$touch"
              />
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model.number="form.quantity"
                label="Cantidad"
                type="number"
                min="1"
                :error-messages="v$.quantity.$errors.map(e => e.$message)"
                @blur="v$.quantity.$touch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model.number="form.unit_price"
                label="Precio Unitario"
                type="number"
                min="0"
                step="0.01"
                :error-messages="v$.unit_price.$errors.map(e => e.$message)"
                @blur="v$.unit_price.$touch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                :model-value="form.quantity * form.unit_price"
                label="Subtotal"
                readonly
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text @click="emit('update:dialog', false)">Cancelar</v-btn>
        <v-btn color="primary" @click="handleSave" :disabled="v$.$invalid">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, numeric, minValue } from '@vuelidate/validators';

const props = defineProps({
  modelValue: Object,
  dialog: Boolean
});

const emit = defineEmits(['save', 'update:dialog']);

const typeOptions = ['commission', 'insurance', 'expense', 'penalty', 'other'];

const form = reactive({
  type: '',
  description: '',
  quantity: 1,
  unit_price: null
});

const rules = {
  type: { required },
  description: { required },
  quantity: { required, numeric, minValue: 1 },
  unit_price: { required, numeric, minValue: 0.01 }
};

const v$ = useVuelidate(rules, form);

watch(() => props.modelValue, (data) => {
  if (data) Object.assign(form, data);
  else Object.assign(form, { type: '', description: '', quantity: 1, unit_price: null });
}, { immediate: true });

const handleSave = async () => {
  const valid = await v$.value.$validate();
  if (!valid) return;
  emit('save', { ...form });
};
</script>
