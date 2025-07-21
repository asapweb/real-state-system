<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from '@/services/axios';

const props = defineProps({
  modelValue: {
    type: [Number, String, null],
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  density: {
    type: String,
    default: 'compact',
  },
  variant: {
    type: String,
    default: 'underlined',
  },
});

const emit = defineEmits(['update:modelValue']);

const taxRates = ref([]);
const loading = ref(false);

async function fetchTaxRates() {
  loading.value = true;
  try {
    const response = await axios.get('/api/tax-rates');
    taxRates.value = response.data;
  } catch (error) {
    console.error('Error fetching tax rates:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchTaxRates);

const selectedValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});
</script>

<template>
  <v-select
    v-model="selectedValue"
    :items="taxRates"
    item-title="name"
    item-value="id"
    :label="label"
    :loading="loading"
    :density="density"
    :variant="variant"
    clearable
  />
</template>
