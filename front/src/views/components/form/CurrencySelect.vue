<template>
  <v-select
    :density="density"
    :variant="variant"
    :hide-details="hideDetails"
    v-model="modelValue"
    :items="currencies"
    item-title="label"
    item-value="value"
    label="Moneda"
    :error-messages="errorMessages"
    clearable
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: String,
  errorMessages: {
    type: Array,
    default: () => [],
  },
  density: { type: String, default: 'compact' },
  variant: { type: String, default: 'solo-filled' },
  hideDetails: { type: [String, Boolean], default: 'auto' },
})

const emit = defineEmits(['update:modelValue'])

const currencies = [
  { label: 'Pesos Argentinos (ARS)', value: 'ARS' },
  { label: 'Dólares Estadounidenses (USD)', value: 'USD' },
]

const modelValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})
</script>
