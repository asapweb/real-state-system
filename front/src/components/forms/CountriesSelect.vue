<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useReferenceDataStore } from '../../stores/referenceData';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    label: {
        type: String,
        default: 'País',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    clearable: { // ¡NUEVO!
        type: Boolean,
        default: false,
    },
    // ¡NUEVO! Define la prop 'rules' para recibir las reglas de validación
    rules: {
        type: Array,
        default: () => [], // Por defecto, es un array vacío
    },
    // ¡NUEVO! Define la prop 'required'
    required: {
        type: Boolean,
        default: false, // Por defecto, no es requerido
    },
});

const emit = defineEmits(['update:modelValue']);

const referenceDataStore = useReferenceDataStore();
const selectedCountry = ref(null);
const loading = ref(false);
const error = ref(null);

const countries = computed(() => {
    const data = referenceDataStore.countries || [];
    return data;
});

onMounted(async () => {
    loading.value = true;
    error.value = null;
    try {
        await referenceDataStore.fetchCountries();
        if (!selectedCountry.value && referenceDataStore.defaultCountry) {
            selectedCountry.value = referenceDataStore.defaultCountry.id;
            emit('update:modelValue', selectedCountry.value);
        }
    } catch (err) {
        error.value = 'Error al cargar países';
        console.error('Failed to fetch countries:', err);
    } finally {
        loading.value = false;
    }
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
            selectedCountry.value = newValue;
        } else {
            console.warn('Invalid modelValue received:', newValue);
        }
    },
    { immediate: true }
);
</script>

<template>
        <v-select
            v-model="selectedCountry"
            :items="countries"
            item-value="id"
            item-title="name"
            :clearable="clearable"
            :label="label"
            :disabled="loading || disabled"
            placeholder="Selecciona un país"
            :rules="rules"   :required="required"
            @update:modelValue="emit('update:modelValue', $event)"
        ></v-select>
        <v-progress-circular v-if="loading" indeterminate size="24" />
        <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
