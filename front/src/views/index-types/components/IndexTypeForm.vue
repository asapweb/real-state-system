<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-title class="text-h6">
        {{ initialData?.id ? 'Editar Tipo de Índice' : 'Nuevo Tipo de Índice' }}
      </v-card-title>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" md="5">
              <!-- Código -->
              <v-text-field
                v-model="formData.code"
                label="Código"
                :error-messages="v$?.code?.$errors?.map((e) => e.$message) || []"
                @blur="v$?.code?.$touch"
                required
              />
            </v-col>
            <v-col cols="12" md="7">
              <!-- Nombre -->
              <v-text-field
                v-model="formData.name"
                label="Nombre"
                :error-messages="v$?.name?.$errors?.map((e) => e.$message) || []"
                @blur="v$?.name?.$touch"
                required
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12" md="3">
              <!-- Frecuencia -->
              <v-select
                v-model="formData.frequency"
                :items="frequencyOptions"
                item-title="label"
                item-value="value"
                label="Frecuencia"
                :error-messages="v$?.frequency?.$errors?.map((e) => e.$message) || []"
                @blur="v$?.frequency?.$touch"
                required
              />
            </v-col>
            <v-col cols="12" md="6">
              <!-- Modo de Cálculo -->
              <v-select
                v-model="formData.calculation_mode"
                :items="calculationModeOptions"
                item-title="label"
                item-value="value"
                label="Modo de Cálculo"
                :error-messages="v$?.calculation_mode?.$errors?.map((e) => e.$message) || []"
                @blur="v$?.calculation_mode?.$touch"
                required
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-switch
                v-model="formData.is_cumulative"
                label="Acumulado"
                color="primary"
                hide-details
              />
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                label="Activo?"
                color="primary"
                hide-details
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="!isValidForm">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, watch, computed, nextTick } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength } from '@vuelidate/validators'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  code: '',
  name: '',
  is_active: true,
  calculation_mode: 'percentage',
  frequency: 'daily',
})

const calculationModeOptions = [
  { label: 'Porcentaje', value: 'percentage', description: 'Aplica directamente el porcentaje de variación del índice' },
  { label: 'Ratio', value: 'ratio', description: 'Calcula la variación entre dos valores del índice (estándar argentino)' },
  { label: 'Cadena Multiplicativa', value: 'multiplicative_chain', description: 'Multiplica coeficientes mensuales entre sí desde el inicio del contrato' },
]

const frequencyOptions = [
  { label: 'Diaria', value: 'daily', description: 'Valores de índice disponibles por fecha específica' },
  { label: 'Mensual', value: 'monthly', description: 'Valores de índice disponibles por mes' },
]

const rules = {
  code: { required, minLength: minLength(2), maxLength: maxLength(10) },
  name: { required, minLength: minLength(3), maxLength: maxLength(255) },
  calculation_mode: {
    required,
    $validator: (value) => ['percentage', 'ratio', 'multiplicative_chain'].includes(value),
    $message: 'El modo de cálculo debe ser percentage, ratio o multiplicative_chain'
  },
  frequency: {
    required,
    $validator: (value) => ['daily', 'monthly'].includes(value),
    $message: 'La frecuencia debe ser daily o monthly'
  },
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

// Observar cambios en formData para debug
watch(formData, (newData) => {
  console.log('Form data changed:', newData)
}, { deep: true })

watch(
  () => props.initialData,
  (data) => {
    console.log('Initial data received:', data)

    if (data?.id) {
      console.log('Editing existing record:', data)
      Object.assign(formData, {
        ...data,
      })
      console.log('Form data after assignment:', { ...formData })

      // Marcar campos como tocados para validación en edición
      nextTick(() => {
        v$.value?.code?.$touch()
        v$.value?.name?.$touch()
        v$.value?.calculation_mode?.$touch()
        v$.value?.frequency?.$touch()
        console.log('Fields marked as touched')
      })
    } else {
      console.log('Creating new record')
      formData.code = ''
      formData.name = ''
      formData.is_active = true
      formData.calculation_mode = 'percentage'
      formData.frequency = 'daily'
      console.log('Form data reset:', { ...formData })

      // Resetear validación para nuevo registro
      v$.value?.$reset()
    }
  },
  { immediate: true },
)

const isValidForm = computed(() => {
  // Verificar que todos los campos requeridos tengan valores válidos
  const codeValid = formData.code && formData.code.length >= 2 && formData.code.length <= 10;
  const nameValid = formData.name && formData.name.length >= 3 && formData.name.length <= 255;
  const calculationModeValid = formData.calculation_mode && ['percentage', 'ratio', 'multiplicative_chain'].includes(formData.calculation_mode);
  const frequencyValid = formData.frequency && ['daily', 'monthly'].includes(formData.frequency);

  console.log('Form validation state:', {
    codeValid,
    nameValid,
    calculationModeValid,
    frequencyValid,
    formData: { ...formData }
  });

  return codeValid && nameValid && calculationModeValid && frequencyValid;
})

const handleSubmit = async () => {
  console.log('Submitting form with validation:', {
    isValid: isValidForm.value,
    formData: { ...formData }
  });

  if (!isValidForm.value) {
    console.log('Form is not valid, submission blocked');
    return;
  }

  console.log('Form is valid, emitting submit');
  emit('submit', { ...formData });
};
</script>
