<template>
  <PropertyServiceTable :property-id="propertyId" :editable="editable" @updated="emit('updated')" />
</template>

<script setup>
import PropertyServiceTable from './PropertyServiceTable.vue';

const props = defineProps({
  propertyId: { type: Number, required: true },
  editable: { type: Boolean, default: true }
});
const emit = defineEmits(['updated']);
</script>
