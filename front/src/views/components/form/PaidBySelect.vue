<template>
  <v-select
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :items="options"
    item-title="label"
    item-value="value"
    label="Pagado por"
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
  { label: 'Inquilino', value: 'tenant' },
  { label: 'Propietario', value: 'owner' },
  { label: 'Inmobiliaria', value: 'agency' },
]
</script>
