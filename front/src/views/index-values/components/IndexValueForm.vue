<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-title class="text-h6">
        {{ initialData?.id ? 'Editar Valor de Índice' : 'Nuevo Valor de Índice' }}
      </v-card-title>
      <v-card-text>
        <v-container>
          <!-- Tipo de Índice -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.index_type_id"
                :items="indexTypeOptions"
                item-title="name"
                item-value="id"
                label="Tipo de Índice"
                :error-messages="v$?.index_type_id?.$errors?.map((e) => e.$message) || []"
                @blur="v$?.index_type_id?.$touch"
                @update:model-value="handleIndexTypeChange"
                required
              />
            </v-col>
          </v-row>

          <!-- Fecha efectiva (unificada) -->
          <template v-if="selectedIndexType">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.effective_date"
                  label="Fecha efectiva"
                  type="date"
                  :error-messages="v$?.effective_date?.$errors?.map((e) => e.$message) || []"
                  @blur="v$?.effective_date?.$touch"
                  required
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-if="selectedIndexType.calculation_mode === 'percentage'"
                  v-model="formData.value"
                  label="Porcentaje"
                  type="number"
                  step="0.01"
                  min="0"
                  max="999.99"
                  suffix="%"
                  :error-messages="v$?.value?.$errors?.map((e) => e.$message) || []"
                  @blur="v$?.value?.$touch"
                  required
                />
                <v-text-field
                  v-else
                  v-model="formData.value"
                  label="Valor"
                  type="number"
                  step="0.01"
                  min="0"
                  :error-messages="v$?.value?.$errors?.map((e) => e.$message) || []"
                  @blur="v$?.value?.$touch"
                  required
                />
              </v-col>
            </v-row>
          </template>
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
import { ref, reactive, watch, computed, onMounted, nextTick } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, numeric, minValue, maxValue } from '@vuelidate/validators'
import axios from '@/services/axios'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  index_type_id: null,
  effective_date: '',
  value: null,
  id: null,
})

const indexTypeOptions = ref([])
const selectedIndexType = ref(null)

// Reglas de validación
const getValidationRules = () => {
  return {
    index_type_id: { required },
    effective_date: { required },
    value: {
      required,
      $validator: (value) => {
        if (!value && value !== 0) return false
        const numValue = parseFloat(value)
        if (isNaN(numValue)) return false
        return numValue >= 0
      },
      $message: 'El valor debe ser un número positivo'
    }
  }
}

const v$ = useVuelidate(getValidationRules(), formData)
const formRef = ref(null)

const fetchIndexTypes = async () => {
  try {
    const { data } = await axios.get('/api/index-types')
    indexTypeOptions.value = data.data
    if (formData.index_type_id) {
      selectedIndexType.value = indexTypeOptions.value.find(type => type.id === formData.index_type_id)
    }
  } catch (e) {
    console.error('Error cargando tipos de índice:', e)
  }
}

const handleIndexTypeChange = () => {
  formData.effective_date = ''
  formData.value = null
  v$.value.$reset()
}

onMounted(() => {
  fetchIndexTypes()
})

// Forzar validación cuando cambien los valores
watch(formData, () => {
  nextTick(() => {
    if (formData.index_type_id) {
      v$.value?.index_type_id?.$touch()
    }
    if (formData.effective_date) {
      v$.value?.effective_date?.$touch()
    }
    if (formData.value) {
      v$.value?.value?.$touch()
    }
  })
}, { deep: true })

watch(
  () => props.initialData,
  (data) => {
    if (data?.id) {
      console.log('Datos recibidos del backend:', data)
      console.log('Fecha original:', data.effective_date)

      // Manejar la fecha correctamente para evitar problemas de zona horaria
      let effectiveDate = ''
      if (data.effective_date) {
        // Convertir la fecha de manera segura sin problemas de zona horaria
        const dateStr = String(data.effective_date)
        console.log('Fecha como string:', dateStr)

        if (dateStr.includes('T')) {
          // Si es una fecha ISO, extraer solo la parte de la fecha
          effectiveDate = dateStr.split('T')[0]
        } else {
          // Si ya es un string en formato YYYY-MM-DD, usarlo directamente
          effectiveDate = dateStr.slice(0, 10)
        }
      }

      console.log('Fecha procesada:', effectiveDate)

      Object.assign(formData, {
        ...data,
        id: data.id,
        effective_date: effectiveDate,
      })
      if (indexTypeOptions.value.length > 0 && formData.index_type_id) {
        selectedIndexType.value = indexTypeOptions.value.find(type => type.id === formData.index_type_id)
      }
    } else {
      formData.index_type_id = null
      formData.effective_date = ''
      formData.value = null
      formData.id = null
      selectedIndexType.value = null
    }
  },
  { immediate: true },
)

watch(
  () => formData.index_type_id,
  (newValue) => {
    if (newValue) {
      selectedIndexType.value = indexTypeOptions.value.find(type => type.id === newValue)
    } else {
      selectedIndexType.value = null
    }
  },
  { immediate: true }
)

const isValidForm = computed(() => {
  if (!selectedIndexType.value) return false
  const baseValid = formData.index_type_id && !v$.value?.index_type_id?.$error
  const dateValid = formData.effective_date && !v$.value?.effective_date?.$error
  const valueValid = formData.value && !v$.value?.value?.$error
  return baseValid && dateValid && valueValid
})

const handleSubmit = async () => {
  if (!isValidForm.value) return

  // Asegurar que la fecha esté en formato YYYY-MM-DD sin problemas de zona horaria
  let effectiveDate = formData.effective_date
  if (effectiveDate) {
    // Si la fecha ya está en formato YYYY-MM-DD, usarla directamente
    if (/^\d{4}-\d{2}-\d{2}$/.test(effectiveDate)) {
      // Ya está en el formato correcto
    } else {
      // Si no está en formato YYYY-MM-DD, convertirla de manera segura
      const dateStr = String(effectiveDate)
      if (dateStr.includes('T')) {
        effectiveDate = dateStr.split('T')[0]
      } else {
        // Intentar parsear como fecha local
        const date = new Date(effectiveDate + 'T00:00:00')
        if (!isNaN(date.getTime())) {
          effectiveDate = date.toISOString().split('T')[0]
        }
      }
    }
  }

  // Si el índice es mensual, redondear la fecha al primer día del mes
  if (selectedIndexType.value?.frequency === 'monthly' && effectiveDate) {
    const [year, month] = effectiveDate.split('-')
    effectiveDate = `${year}-${month}-01`
  }

  const submitData = {
    id: formData.id,
    index_type_id: formData.index_type_id,
    effective_date: effectiveDate,
    value: formData.value,
  }

  console.log('Enviando datos:', submitData)
  emit('submit', submitData)
}
</script>
