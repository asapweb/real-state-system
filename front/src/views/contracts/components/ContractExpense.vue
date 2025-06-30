<template>
  <ContractExpenseTable :contract-id="contractId" :editable="editable" @updated="emit('updated')" />
</template>

<script setup>
import ContractExpenseTable from './ContractExpenseTable.vue'

const props = defineProps({
  contractId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})

const emit = defineEmits(['updated'])
</script>
