<!-- src/components/KpiCard.vue -->
<template>
  <div
    class="kpi-card d-flex flex-column pa-4 rounded cursor-pointer"
    @click="$emit('click')"
    :class="{ 'kpi-card--hover': hover }"
  >
    <!-- Título + Tooltip -->
    <div class="text-caption text-medium-emphasis d-flex align-center">
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

    <!-- Contenido principal: valor o spinner -->
    <div class="text-h6 mt-1 d-flex align-center">
      <v-progress-circular
        v-if="loading"
        indeterminate
        size="20"
        width="2"
        color="primary"
        class="me-2"
      />
      <span v-else>{{ value }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
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
    required: true
  },
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
  }
})

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
