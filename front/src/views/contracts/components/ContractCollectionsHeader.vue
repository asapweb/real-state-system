<template>
  <div class="d-flex justify-between align-center mb-4">
    <div>
      <h2 class="text-h6">Cobranza mensual</h2>
      <div class="text-caption text-medium-emphasis">
        {{ contract?.client_name }} — {{ contract?.property_address }}
      </div>
    </div>

    <div class="d-flex align-center gap-4">
      <v-text-field
        v-model="internalPeriod"
        type="month"
        density="compact"
        label="Período"
        hide-details
        class="mr-2"
        @change="$emit('changePeriod', internalPeriod)"
        style="max-width: 150px"
      />

      <v-btn color="primary" @click="$emit('generate')">
        Generar cobranza
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  contract: Object,
  period: String
})

const emit = defineEmits(['changePeriod', 'generate'])

const internalPeriod = ref(props.period)

watch(() => props.period, (val) => {
  internalPeriod.value = val
})
</script>

<style scoped>
.gap-4 {
  gap: 1rem;
}
</style>
