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
        default: 'Condición fiscal',
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
const selectedTaxCondition = ref(null);
const loading = ref(false);
const error = ref(null);

const taxConditions = computed(() => {
    const data = referenceDataStore.taxConditions || [];
    console.log('Tax conditions data:', data);
    return data;
});

onMounted(async () => {
    loading.value = true;
    error.value = null;
    try {
        await referenceDataStore.fetchTaxConditions();
        // Si no hay un valor pre-seleccionado (modelValue) y el store tiene datos,
        // intenta seleccionar el default '51'
        if ((props.modelValue === null || props.modelValue === undefined) && taxConditions.value.length > 0) {
            const defaultTaxCondition = taxConditions.value.find(tc => tc.code === '51');
            // Solo actualiza si hay un valor por defecto encontrado y no es el mismo que el actual
            if (defaultTaxCondition && selectedTaxCondition.value !== defaultTaxCondition.id) {
                selectedTaxCondition.value = defaultTaxCondition.id;
                emit('update:modelValue', selectedTaxCondition.value);
            }
        }
    } catch (err) {
        error.value = 'Error al cargar condiciones fiscales';
        console.error('Failed to fetch tax conditions:', err);
    } finally {
        loading.value = false;
    }
});

watch(
    () => props.modelValue,
    (newValue) => {
        // Asegúrate de que solo actualizas si el valor es realmente diferente para evitar loops
        if (newValue !== selectedTaxCondition.value) {
            if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
                selectedTaxCondition.value = newValue;
            } else {
                console.warn('Invalid modelValue received by TaxConditionsSelect:', newValue);
            }
        }
    },
    { immediate: true }
);

// Observar selectedTaxCondition para emitir el evento si cambia internamente
watch(selectedTaxCondition, (newValue) => {
    if (newValue !== props.modelValue) { // Solo emite si el valor interno es diferente al de la prop
        emit('update:modelValue', newValue);
    }
});
</script>

<template>
        <v-select
            v-model="selectedTaxCondition"
            :items="taxConditions"
            item-value="id"
            item-title="description"
            :clearable="clearable"
            :label="label"
            :disabled="loading || disabled"
            placeholder="Selecciona una condición fiscal"
            :rules="rules"   :required="required" >
        </v-select>
        <v-progress-circular v-if="loading" indeterminate size="24" />
        <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
