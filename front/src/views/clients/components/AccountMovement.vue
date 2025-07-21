<template>
  <div>
    <AccountBalanceIndicators :client-id="clientId" />
  </div>
</template>

<script setup>
import AccountBalanceIndicators from './AccountBalanceIndicators.vue'
defineProps({ clientId: Number })
</script>
