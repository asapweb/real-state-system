<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12">
              <v-select
                v-model="form.service_type"
                :items="serviceTypes"
                item-title="label"
                item-value="value"
                label="Tipo de servicio"
                :error-messages="v$.service_type.$errors.map((e) => e.$message)"
                @blur="v$.service_type.$touch"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.account_number"
                label="Número de cuenta"
                :error-messages="v$.account_number.$errors.map((e) => e.$message)"
                @blur="v$.account_number.$touch"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.provider_name"
                label="Nombre del proveedor"
                :error-messages="v$.provider_name.$errors.map((e) => e.$message)"
                @blur="v$.provider_name.$touch"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.owner_name"
                label="Titular del servicio"
                :error-messages="v$.owner_name.$errors.map((e) => e.$message)"
                @blur="v$.owner_name.$touch"
              />
            </v-col>

            <v-col cols="12">
              <v-switch v-model="form.is_active" label="¿Servicio activo?" />
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
import { required } from '@vuelidate/validators'

const props = defineProps({
  loading: Boolean,
  initialData: Object,
})
const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  service_type: '',
  account_number: '',
  provider_name: '',
  owner_name: '',
  is_active: true, // nuevo campo
})

const rules = {
  service_type: { required },
  account_number: { required },
  provider_name: { required },
  owner_name: { required },
}

const v$ = useVuelidate(rules, form)

watch(
  () => props.initialData,
  (data) => {
    if (data) {
      form.service_type = data.service_type ?? ''
      form.account_number = data.account_number ?? ''
      form.provider_name = data.provider_name ?? ''
      form.owner_name = data.owner_name ?? ''
      form.is_active = data.is_active ?? true
    } else {
      form.service_type = ''
      form.account_number = ''
      form.provider_name = ''
      form.owner_name = ''
      form.is_active = true
    }
  },
  { immediate: true },
)

const serviceTypes = [
  { label: 'Electricidad', value: 'electricity' },
  { label: 'Agua', value: 'water' },
  { label: 'Gas', value: 'gas' },
  { label: 'Internet', value: 'internet' },
  { label: 'Teléfono', value: 'phone' },
]

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...form })
}
</script>
