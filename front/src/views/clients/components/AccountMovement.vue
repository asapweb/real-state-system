<template>
  <div>
    <h2 class="text-h5 mb-4">Cuenta Corriente</h2>
    <AccountMovementTable :client-id="clientId" />
  </div>
</template>

<script setup>
import AccountMovementTable from './AccountMovementTable.vue'
defineProps({ clientId: Number })
</script>
