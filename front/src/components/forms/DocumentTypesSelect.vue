<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useReferenceDataStore } from '../../stores/referenceData';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    clientType: {
        type: String,
        default: 'individual',
    },
    label: {
        type: String,
        default: 'Tipo de documento',
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
const selectedDocumentType = ref(null);
const loading = ref(false);
const error = ref(null);

const filteredDocumentTypes = computed(() => {
    const data = referenceDataStore.getDocumentTypesByClientType(props.clientType);
    console.log('Document types data for', props.clientType, ':', data);
    return data;
});

onMounted(async () => {
    loading.value = true;
    error.value = null;
    try {
        await referenceDataStore.fetchDocumentTypes();
        // Si no hay un valor pre-seleccionado (modelValue) y el store tiene datos,
        // intenta seleccionar el default '96' para individuos
        if ((props.modelValue === null || props.modelValue === undefined) && filteredDocumentTypes.value.length > 0) {
            const defaultDocType = filteredDocumentTypes.value.find(dt => dt.code === '96');
            // Solo actualiza si hay un valor por defecto encontrado y no es el mismo que el actual
            if (defaultDocType && selectedDocumentType.value !== defaultDocType.id) {
                selectedDocumentType.value = defaultDocType.id;
                emit('update:modelValue', selectedDocumentType.value);
            }
        }
    } catch (err) {
        error.value = 'Error al cargar tipos de documento';
        console.error('Failed to fetch document types:', err);
    } finally {
        loading.value = false;
    }
});

watch(
    () => props.modelValue,
    (newValue) => {
        // Asegúrate de que solo actualizas si el valor es realmente diferente para evitar loops
        if (newValue !== selectedDocumentType.value) {
            if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
                selectedDocumentType.value = newValue;
            } else {
                console.warn('Invalid modelValue received by DocumentTypesSelect:', newValue);
            }
        }
    },
    { immediate: true }
);

watch(
    () => props.clientType,
    (newClientType, oldClientType) => {
        // No resetear si el tipo de cliente no ha cambiado y ya hay un valor.
        // Esto previene un reset innecesario cuando el componente se monta por primera vez.
        if (newClientType === oldClientType && selectedDocumentType.value !== null) {
            return;
        }

        selectedDocumentType.value = null;
        emit('update:modelValue', selectedDocumentType.value); // Emitir null inmediatamente

        // Intentar seleccionar el tipo de documento predeterminado después de filtrar
        const defaultDocType = filteredDocumentTypes.value.find(dt => dt.code === '96');
        if (defaultDocType) {
            selectedDocumentType.value = defaultDocType.id;
            emit('update:modelValue', selectedDocumentType.value); // Emitir el nuevo valor por defecto
        }
    },
    { immediate: true }
);

// Observar selectedDocumentType para emitir el evento si cambia internamente
watch(selectedDocumentType, (newValue) => {
    if (newValue !== props.modelValue) { // Solo emite si el valor interno es diferente al de la prop
        emit('update:modelValue', newValue);
    }
});
</script>

<template>
        <v-select
            v-model="selectedDocumentType"
            :items="filteredDocumentTypes"
            :clearable="clearable"
            item-value="id"
            item-title="name"
            :label="label"
            :disabled="loading || disabled"
            placeholder="Selecciona un tipo de documento"
            :rules="rules"   :required="required" >
        </v-select>
        <v-progress-circular v-if="loading" indeterminate size="24" />
        <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
