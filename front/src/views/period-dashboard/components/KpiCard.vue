<template>
  <v-card :loading="loading" class="h-100">
    <v-card-text>
      <div class="d-flex align-center justify-space-between mb-2">
        <div class="text-subtitle-2 text-medium-emphasis">{{ title }}</div>
        <v-tooltip v-if="tooltip" text="{{ tooltip }}">
          <template #activator="{ props }">
            <v-icon v-bind="props" size="18">mdi-information</v-icon>
          </template>
        </v-tooltip>
      </div>
      <div class="text-h4">{{ formatValue(value) }}</div>
      <slot />
    </v-card-text>
  </v-card>
</template>

<script setup>
const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  loading: { type: Boolean, default: false },
  tooltip: { type: String, default: '' },
})

function formatValue(v) {
  if (typeof v === 'number') return new Intl.NumberFormat('es-AR').format(v)
  return v ?? '-'
}
</script>
