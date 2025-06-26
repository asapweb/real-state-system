<template>
  <PropertyOwnerTable :property-id="propertyId" :editable="editable" @updated="emit('updated')" />
</template>

<script setup>
import PropertyOwnerTable from './PropertyOwnerTable.vue'

defineProps({
  propertyId: { type: Number, required: true },
  editable: { type: Boolean, default: true },
})
const emit = defineEmits(['updated'])
</script>
