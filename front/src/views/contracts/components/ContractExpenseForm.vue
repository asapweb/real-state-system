<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card title="Gasto asociado al contrato">
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.period"
                label="Mes (YYYY-MM)"
                :error-messages="v$.period.$errors.map((e) => e.$message)"
                @blur="v$.period.$touch"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.due_date"
                label="Vencimiento"
                type="date"
                :error-messages="v$.due_date.$errors.map((e) => e.$message)"
                @blur="v$.due_date.$touch"
              />
            </v-col>

            <v-col cols="12" md="6">
              <ServiceTypeSelect
                v-model="form.service_type"
                :error-messages="v$.service_type.$errors.map((e) => e.$message)"
              />
            </v-col>

            <v-col cols="12" md="6">
              <PaidBySelect
                v-model="form.paid_by"
                :error-messages="v$.paid_by.$errors.map((e) => e.$message)"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.amount"
                label="Importe"
                type="number"
                :error-messages="v$.amount.$errors.map((e) => e.$message)"
                @blur="v$.amount.$touch"
              />
            </v-col>

            <v-col cols="12" md="6">
              <CurrencySelect
                v-model="form.currency"
                :error-messages="v$.currency.$errors.map((e) => e.$message)"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch v-model="form.is_paid" label="Pagado" />
            </v-col>

            <v-col cols="12">
              <v-textarea v-model="form.description" label="Descripción" rows="2" auto-grow />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, minValue } from '@vuelidate/validators'
import ServiceTypeSelect from '@/views/components/form/ServiceTypeSelect.vue'
import PaidBySelect from '@/views/components/form/PaidBySelect.vue'
import CurrencySelect from '@/views/components/form/CurrencySelect.vue'

const props = defineProps({
  loading: Boolean,
  initialData: Object,
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  id: null,
  service_type: '',
  amount: null,
  currency: 'ARS',
  paid_by: '',
  is_paid: false,
  period: '',
  due_date: '',
  description: '',
})

const rules = {
  service_type: { required },
  amount: { required, minValue: minValue(0.01) },
  currency: { required },
  paid_by: { required },
  period: { required },
  due_date: { required },
}

const v$ = useVuelidate(rules, form)

watch(
  () => props.initialData,
  (data) => {
    if (data) {
      Object.assign(form, {
        id: data.id ?? null,
        service_type: data.service_type ?? '',
        amount: data.amount ?? null,
        currency: data.currency ?? 'ARS',
        paid_by: data.paid_by ?? '',
        is_paid: data.is_paid ?? false,
        period: data.period?.substring(0, 7) || '', // formato YYYY-MM
        due_date: data.due_date?.substring(0, 10) || '', // formato YYYY-MM-DD
        description: data.description ?? '',
      })
    } else {
      Object.assign(form, {
        id: null,
        service_type: '',
        amount: null,
        currency: 'ARS',
        paid_by: '',
        is_paid: false,
        included_in_collection: false,
        period: '',
        due_date: '',
        description: '',
      })
    }
  },
  { immediate: true },
)

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...form })
}
</script>
