<template>
  <div class="d-flex justify-space-between mt-8">
    <div>
      <v-btn
        icon="mdi-filter"
        variant="text"
        size="small"
        :style="{ color: '#718096' }"
        @click="changePeriod(-1)"
        density="comfortable"
      ></v-btn>
      <v-btn
        icon="mdi-chevron-left"
        variant="text"
        size="small"
        :style="{ color: '#718096' }"
        @click="changePeriod(-1)"
        density="comfortable"
      ></v-btn>
      <v-btn
        icon="mdi-chevron-right"
        variant="text"
        size="small"
        :style="{ color: '#718096' }"
        @click="changePeriod(1)"
        density="comfortable"
      ></v-btn>
      <v-btn
        variant="text"
        size="small"
        :style="{ color: '#718096' }"
        @click="resetToCurrentMonth"
        density="comfortable"
      >
        ESTE MES
      </v-btn>
    </div>
    <div class="text-h6 me-2">
      {{ formatPeriodWithMonthName(period).charAt(0).toUpperCase() + formatPeriodWithMonthName(period).slice(1) }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatPeriodWithMonthName } from '@/utils/date-formatter'

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const period = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const changePeriod = (direction) => {
  const [year, month] = period.value.split('-').map(Number)
  const newMonth = month + direction
  if (newMonth < 1) {
    period.value = `${year - 1}-12`
  } else if (newMonth > 12) {
    period.value = `${year + 1}-01`
  } else {
    period.value = `${year}-${String(newMonth).padStart(2, '0')}`
  }
}

const resetToCurrentMonth = () => {
  const now = new Date()
  const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  period.value = currentMonth
}
</script>

