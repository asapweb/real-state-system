<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card title="Agregar propietario">
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12">
              <ClientAutocomplete
                v-model="form.client_id"
                :error-messages="v$.client_id.$errors.map((e) => e.$message)"
                @blur="v$.client_id.$touch"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="form.ownership_percentage"
                label="Porcentaje de titularidad"
                suffix="%"
                type="number"
                :error-messages="v$.ownership_percentage.$errors.map((e) => e.$message)"
                @blur="v$.ownership_percentage.$touch"
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
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
import { required, numeric, minValue, maxValue } from '@vuelidate/validators'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'

const props = defineProps({
  loading: Boolean,
  initialData: Object, // <-- Agregado
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  client_id: null,
  ownership_percentage: '',
})

const rules = {
  client_id: { required },
  ownership_percentage: { required, numeric, minValue: minValue(0), maxValue: maxValue(100) },
}

const v$ = useVuelidate(rules, form)

// Cargar datos cuando se pasa initialData
watch(
  () => props.initialData,
  (newData) => {
    if (newData) {
      form.client_id = newData.client_id ?? null
      form.ownership_percentage = newData.ownership_percentage ?? ''
    } else {
      // Si es nuevo, limpiar el form
      form.client_id = null
      form.ownership_percentage = ''
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
