<template>
  <div class="kpi-card d-flex flex-column pa-4 rounded">
    <div class="text-caption text-medium-emphasis d-flex align-center justify-space-between">
      {{ title }}
      <v-tooltip v-if="tooltip" location="top" :text="tooltip">
        <template #activator="{ props }">
          <v-icon v-bind="props" :icon="icon" size="14" class="ms-1" role="button" :aria-label="`Ayuda sobre ${title}`" tabindex="0" />
        </template>
      </v-tooltip>
    </div>

    <div class="d-flex align-center justify-start mt-2">
      <v-progress-circular
        :model-value="clamped"
        :size="size"
        :width="stroke"
        :color="currentColor"
      >
        <span class="text-subtitle-2">{{ displayValue }}</span>
      </v-progress-circular>

      <div class="ms-4 d-flex flex-column">
        <small v-if="subtitle" class="text-medium-emphasis mt-1">{{ subtitle }}</small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: Number, required: false, default: null },        // 0–100
  subtitle: { type: String, default: '' },
  tooltip: { type: String, default: '' },
  size: { type: Number, default: 72 },
  stroke: { type: Number, default: 8 },
  icon: { type: String, default: 'mdi-help-circle-outline' },
  thresholds: {
    type: Array,
    default: () => ([
      { lte: 40, color: 'error' },
      { lte: 80, color: 'warning' },
      { lte: 100, color: 'success' }
    ])
  }
})

const hasValue = computed(() => props.value !== null && props.value !== undefined)
const clamped = computed(() => {
  if (!hasValue.value) {
    return 0
  }
  return Math.max(0, Math.min(100, Math.round(props.value)))
})
const displayValue = computed(() => (hasValue.value ? `${clamped.value}%` : 'N/A'))
const currentColor = computed(() => {
  if (!hasValue.value) {
    return 'grey'
  }
  const t = props.thresholds.find(t => clamped.value <= t.lte)
  return t?.color ?? 'primary'
})
</script>

<style scoped>
.kpi-card { border: 1px solid #e2e8f0; }
</style>
