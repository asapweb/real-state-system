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
        default: 'Tipo de propiedad',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    clearable: { // ¡NUEVO!
        type: Boolean,
        default: false,
    },
    rules: {
        type: Array,
        default: () => [],
    },
    selectDefault: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const referenceDataStore = useReferenceDataStore();
const selectedPropertyType = ref(null);
const loading = ref(false);
const error = ref(null);

const propertypes = computed(() => {
    const data = referenceDataStore.propertyTypes || [];
    return data;
});

onMounted(async () => {
    loading.value = true;
    error.value = null;
    try {
        await referenceDataStore.fetchPropertyTypes();
        if (props.selectDefault && !selectedPropertyType.value && referenceDataStore.defaultPropertyType) {
            selectedPropertyType.value = referenceDataStore.defaultPropertyType.id;
            emit('update:modelValue', selectedPropertyType.value);
        }
    } catch (err) {
        error.value = 'Error al cargar países';
        console.error('Failed to fetch propertypes:', err);
    } finally {
        loading.value = false;
    }
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
            selectedPropertyType.value = newValue;
        } else {
            console.warn('Invalid modelValue received:', newValue);
        }
    },
    { immediate: true }
);
</script>

<template>
        <v-select
            v-model="selectedPropertyType"
            :items="propertypes"
            density="comfortable"
            item-value="id"
            item-title="name"
            :clearable="clearable"
            :label="label"
            :disabled="loading || disabled"
            placeholder="Selecciona un Tipo de propiedad"
            :rules="rules"   :required="required"
            @update:modelValue="emit('update:modelValue', $event)"
        ></v-select>
        <v-progress-circular v-if="loading" indeterminate size="24" />
        <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
