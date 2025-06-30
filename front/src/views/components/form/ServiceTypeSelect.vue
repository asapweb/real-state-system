<template>
  <v-select
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :items="options"
    item-title="label"
    item-value="value"
    label="Tipo de servicio"
    :error-messages="errorMessages"
    clearable
  />
</template>

<script setup>
defineProps({
  modelValue: String,
  errorMessages: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const options = [
  { label: 'Electricidad', value: 'electricity' },
  { label: 'Agua', value: 'water' },
  { label: 'Gas', value: 'gas' },
  { label: 'Internet', value: 'internet' },
  { label: 'Teléfono', value: 'phone' },
]
</script>
