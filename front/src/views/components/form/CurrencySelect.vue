<template>
  <v-select
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
