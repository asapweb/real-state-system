<template>
  <v-form @submit.prevent="handleSubmit" ref="formRef">
    <v-card>
      <v-card-text>
        <v-container>
          <v-row>
            <!-- Cliente -->
            <v-col cols="12" md="6">
              <ClientAutocomplete
                v-model="formData.client_id"
                :error-messages="v$.client_id.$errors.map((e) => e.$message)"
              />
            </v-col>

            <!-- Rol -->
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.role"
                :items="roleOptions"
                item-title="label"
                item-value="value"
                label="Rol en el contrato"
                :error-messages="v$.role.$errors.map((e) => e.$message)"
                @blur="v$.role.$touch"
              />
            </v-col>

            <!-- Porcentaje de propiedad -->
            <v-col cols="12" md="6" v-if="formData.role === 'owner'">
              <v-text-field
                v-model="formData.ownership_percentage"
                label="Porcentaje de propiedad"
                type="number"
                min="0"
                max="100"
              />
            </v-col>

            <!-- Responsable principal -->
            <v-col cols="12" md="6">
              <v-switch v-model="formData.is_primary" label="Responsable principal" />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" color="blue-grey" @click="emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" type="submit" :loading="loading" :disabled="v$.$invalid">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required } from '@vuelidate/validators'
import ClientAutocomplete from '@/views/components/ClientAutocomplete.vue'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const formData = reactive({
  client_id: null,
  role: null,
  ownership_percentage: null,
  is_primary: false,
})

const rules = {
  client_id: { required },
  role: { required },
}

const v$ = useVuelidate(rules, formData)
const formRef = ref(null)

const roleOptions = [
  { value: 'tenant', label: 'Inquilino' },
  { value: 'guarantor', label: 'Garante' },
  { value: 'owner', label: 'Propietario' },
]

onMounted(() => {
  if (props.initialData?.id) {
    Object.assign(formData, props.initialData)
  }
})

const handleSubmit = async () => {
  const isValid = await v$.value.$validate()
  if (!isValid) return
  emit('submit', { ...formData })
}
</script>
