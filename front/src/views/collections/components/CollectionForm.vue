<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Cliente y Contrato -->
          <v-row>
            <v-col cols="12" md="6">
              <ClientAutocomplete v-model="formData.client_id" />
            </v-col>
            <v-col cols="12" md="6">
              <ContractAutocomplete v-model="formData.contract_id" :client-id="formData.client_id" />
            </v-col>
          </v-row>

          <!-- Fechas y Moneda -->
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.issue_date"
                label="Fecha de Emisión"
                type="date"
                :error-messages="v$.issue_date.$errors.map(e => e.$message)"
                @blur="v$.issue_date.$touch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="formData.due_date"
                label="Fecha de Vencimiento"
                type="date"
                :error-messages="v$.due_date.$errors.map(e => e.$message)"
                @blur="v$.due_date.$touch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="formData.currency"
                :items="['ARS', 'USD']"
                label="Moneda"
                :error-messages="v$.currency.$errors.map(e => e.$message)"
                @blur="v$.currency.$touch"
              />
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <!-- Lista de ítems -->
          <CollectionItemTable
            :items="formData.items"
            @edit="editItem"
            @delete="deleteItem"
          />

          <v-btn color="primary" variant="text" @click="openItemDialog">+ Agregar ítem</v-btn>

          <CollectionItemForm
            v-model="itemDialog.model"
            v-model:dialog="itemDialog.open"
            @save="saveItem"
          />
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn color="blue-grey" variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid || formData.items.length === 0">
          Guardar Cobranza
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue';
import ContractAutocomplete from '@/views/components/ContractAutocomplete.vue';
import CollectionItemTable from '@/views/collections/components/CollectionItemTable.vue';
import CollectionItemForm from '@/views/collections/components/CollectionItemForm.vue';

const props = defineProps({
  initialData: Object,
  loading: Boolean
});

const emit = defineEmits(['submit', 'cancel']);

const today = new Date().toISOString().slice(0, 10);

const formData = reactive({
  client_id: null,
  contract_id: null,
  issue_date: today,
  due_date: '',
  currency: 'ARS',
  items: []
});

const rules = {
  client_id: { required },
  issue_date: { required },
  due_date: { required },
  currency: { required }
};

const v$ = useVuelidate(rules, formData);

watch(() => props.initialData, (data) => {
  if (data) Object.assign(formData, data);
}, { immediate: true });

const itemDialog = reactive({
  open: false,
  model: null,
  index: null
});

const openItemDialog = () => {
  itemDialog.model = null;
  itemDialog.index = null;
  itemDialog.open = true;
};

const editItem = (item, index) => {
  itemDialog.model = { ...item };
  itemDialog.index = index;
  itemDialog.open = true;
};

const saveItem = (item) => {
  if (itemDialog.index !== null) {
    formData.items[itemDialog.index] = item;
  } else {
    formData.items.push(item);
  }
  itemDialog.open = false;
};

const deleteItem = (index) => {
  formData.items.splice(index, 1);
};

const handleSubmit = async () => {
  const isValid = await v$.value.$validate();
  if (!isValid || formData.items.length === 0) return;
  emit('submit', formData);
};
</script>
