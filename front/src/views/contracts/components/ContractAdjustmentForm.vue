<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <!-- Fecha -->
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.effective_date"
                label="Fecha de aplicación"
                type="date"
                :error-messages="v$.effective_date.$errors.map(e => e.$message)"
                @blur="v$.effective_date.$touch"
                required
              />
            </v-col>
          </v-row>

          <!-- Tipo + Valor o Índice -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.type"
                :items="adjustmentTypes"
                item-title="label"
                item-value="value"
                label="Tipo de ajuste"
                :error-messages="v$.type.$errors.map(e => e.$message)"
                @blur="v$.type.$touch"
                required
              />
            </v-col>

            <v-col cols="12" md="6" v-if="formData.type === 'index'">
              <v-select
                v-model="formData.index_type_id"
                :items="indexTypes"
                item-title="name"
                item-value="id"
                label="Índice aplicado"
                :error-messages="v$.index_type_id.$errors.map(e => e.$message)"
                @blur="v$.index_type_id.$touch"
                required
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.value"
                :label="formData.type === 'index' ? 'Valor calculado del índice' : 'Valor del ajuste'"
                type="number"
                min="0"
                :error-messages="v$.value.$errors.map(e => e.$message)"
                @blur="v$.value.$touch"
                :required="formData.type !== 'index'"
              />
            </v-col>
          </v-row>

          <!-- Notas -->
          <v-row>
            <v-col cols="12">
              <v-textarea
                v-model="formData.notes"
                label="Notas"
                rows="2"
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
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, numeric, minValue } from '@vuelidate/validators'
import axios from '@/services/axios'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  effective_date: '',
  type: '',
  value: null,
  index_type_id: null,
  notes: ''
})

const rules = {
  effective_date: { required },
  type: { required },
  value: {
  required: () => formData.type !== 'index',
  numeric,
  minValue: minValue(0)
},
  index_type_id: {
    required: () => formData.type === 'index'
  }
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

const adjustmentTypes = [
  { label: 'Fijo', value: 'fixed' },
  { label: 'Porcentaje', value: 'percentage' },
  { label: 'Índice', value: 'index' },
  { label: 'Negociado', value: 'negotiated' }
]

const indexTypes = ref([])

const fetchIndexTypes = async () => {
  try {
    const { data } = await axios.get('/api/index-types')
    indexTypes.value = data
  } catch (e) {
    console.error('Error cargando tipos de índice:', e)
  }
}

onMounted(() => {
  fetchIndexTypes()
})

watch(
  () => props.initialData,
  (data) => {
    if (data?.id) {
      Object.assign(formData, {
        ...data,
        effective_date: data.effective_date?.substring(0, 10) || ''
      })
    } else {
      formData.effective_date = ''
      formData.type = ''
      formData.value = null
      formData.index_type_id = null
      formData.notes = ''
    }
  },
  { immediate: true }
)

watch(() => formData.type, (type) => {
  if (type === 'index') {
    formData.value = null
    v$.value.value.$reset()
  } else {
    formData.index_type_id = null
    v$.value.index_type_id.$reset()
  }
  v$.value.$touch()
})

watch(() => formData.index_type_id, () => {
  if (formData.type === 'index') v$.value.index_type_id.$touch()
})
watch(() => formData.value, () => {
  if (formData.type !== 'index') v$.value.value.$touch()
})

const isValidForm = computed(() => {
  if (formData.type === 'index') {
    return !v$.value.effective_date.$invalid &&
           !v$.value.type.$invalid &&
           !v$.value.index_type_id.$invalid
  }
  return !v$.value.effective_date.$invalid &&
         !v$.value.type.$invalid &&
         !v$.value.value.$invalid
})

const handleSubmit = async () => {
  if (!isValidForm.value) return
  emit('submit', { ...formData })
}
</script>
