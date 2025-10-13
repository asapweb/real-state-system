<!-- src/components/KpiCard.vue -->
<template>
  <div
  style="height: 100%;"
    class="kpi-card d-flex flex-column pa-4 rounded cursor-pointer"
    @click="$emit('click')"
    :class="{ 'kpi-card--hover': hover }"
  >

    <!-- Título + Tooltip -->
    <div class="text-caption text-medium-emphasis d-flex align-center justify-space-between mb-1">
      {{ title }}
      <v-tooltip
        v-if="tooltip"
        location="top"
        :text="tooltip"
      >
        <template #activator="{ props }">
          <v-icon
            v-bind="props"
            :icon="icon"
            size="14"
            class="ms-1"
            role="button"
            :aria-label="`Ayuda sobre ${title}`"
            tabindex="0"
          />
        </template>
      </v-tooltip>
    </div>
    <v-progress-circular
        v-if="loading"
        indeterminate
        size="20"
        width="2"
        color="primary"
        class="me-2"
      />
      <div v-else>
        <div class="d-flex align-center justify-space-between" :class="hasValue ? `text-${currentColor}` : 'text-medium-emphasis'">
          <div class="text-h6">{{ displayValue }}</div>
          <small class="text-medium-emphasis">{{ subtitle }}</small>
        </div>
        <v-progress-linear
          v-if="hasValue"
          :model-value="clamped"
          :color="currentColor"
          height="6"
          class="my-1 rounded-pill"
        />
        <div v-else class="text-caption text-medium-emphasis">Sin datos</div>
      </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  tooltip: {
    type: String,
    default: ''
  },
  value: {
    type: [String, Number],
    required: false,
    default: null
  },
  subtitle: { type: String, default: '' },
  unit: { type: String, default: '' },

  icon: {
    type: String,
    default: 'mdi-help-circle-outline'
  },
  hover: {
    type: Boolean,
    default: true // Si false, no aplica el efecto hover
  },
  loading: {
    type: Boolean,
    default: false // ✅ Nueva prop: indica si está cargando
  },
  thresholds: {
    type: Array,
    default: () => ([
      // { lte: 40, color: 'error' },
      // { lte: 80, color: 'warning' },
      // { lte: 100, color: 'success' }
      { lte: 40, color: 'error' },
      { lte: 80, color: 'warning' },
      { lte: 100, color: 'info' },
      { lte: 101, color: 'success' }
    ])
  }
})
const hasValue = computed(() => props.value !== null && props.value !== undefined)
const clamped = computed(() => {
  if (!hasValue.value) {
    return 0
  }
  const numeric = typeof props.value === 'string' ? Number(props.value) : props.value
  return Math.max(0, Math.min(100, Math.round(numeric)))
})
const currentColor = computed(() => {
  if (!hasValue.value) {
    return 'grey'
  }
  const t = props.thresholds.find(t => clamped.value < t.lte)
  return t?.color ?? 'primary'
})
const displayValue = computed(() => (hasValue.value ? `${clamped.value}%` : 'N/A'))
defineEmits(['click'])
</script>

<style scoped>
.kpi-card {
  transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease;
  border: 1px solid transparent;
  border: 1px solid #e2e8f0;
}

.kpi-card--hover:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
  /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); */
}
</style>
