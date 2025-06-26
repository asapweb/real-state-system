<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useReferenceDataStore } from '../../stores/referenceData';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    countryId: {
        type: [Number, String, null],
        default: null,
        // required: true,
    },
    label: {
        type: String,
        default: 'Estado/Provincia',
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
const selectedState = ref(null);
const loading = ref(false);
const error = ref(null);

const states = computed(() => {
    const data = referenceDataStore.states[props.countryId] || [];
    return data;
});

onMounted(async () => {
  if (props.countryId) {
      loading.value = true;
      error.value = null;
      try {
          await referenceDataStore.fetchStates(props.countryId);
          const statesList = referenceDataStore.states[props.countryId] || [];
          // Si hay un state_id válido, seleccionarlo
          if (props.modelValue && statesList.some(s => s.id === props.modelValue)) {
              selectedState.value = props.modelValue;
          } else if (statesList.length) {
              // Si no hay state_id válido, seleccionar default si existe
              const defaultState = statesList.find(s => s.default);
              if (defaultState) {
                  selectedState.value = defaultState.id;
                  emit('update:modelValue', defaultState.id);
              }
          }
      } catch (err) {
          error.value = 'Error al cargar estados';
          console.error('Failed to fetch states:', err);
      } finally {
          loading.value = false;
      }
  }
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue === null || typeof newValue === 'number' || typeof newValue === 'string') {
            selectedState.value = newValue;
        } else {
            console.warn('Invalid modelValue received:', newValue);
        }
    },
    { immediate: true }
);

watch(
    () => props.countryId,
    async (newCountryId) => {
        if (!newCountryId) {
            selectedState.value = null;
            emit('update:modelValue', null);
            return;
        }
        loading.value = true;
        error.value = null;
        try {
            await referenceDataStore.fetchStates(newCountryId);
            const statesList = referenceDataStore.states[newCountryId] || [];
            // Si el modelValue es válido para el nuevo país, seleccionarlo
            if (props.modelValue && statesList.some(s => s.id === props.modelValue)) {
                selectedState.value = props.modelValue;
                emit('update:modelValue', props.modelValue);
            } else if (statesList.length) {
                // Si no hay modelValue válido, seleccionar default si existe
                const defaultState = statesList.find(s => s.default);
                if (defaultState) {
                    selectedState.value = defaultState.id;
                    emit('update:modelValue', defaultState.id);
                } else {
                    selectedState.value = null;
                    emit('update:modelValue', null);
                }
            } else {
                selectedState.value = null;
                emit('update:modelValue', null);
            }
        } catch (err) {
            error.value = 'Error al cargar estados';
            console.error('Failed to fetch states:', err);
        } finally {
            loading.value = false;
        }
    },
    { immediate: true }
);
</script>

<template>
        <v-select
            v-model="selectedState"
            :items="states"
            item-value="id"
            item-title="name"
            :label="label"
            :clearable="clearable"
            :disabled="loading || disabled || !props.countryId"
            placeholder="Selecciona un estado"
            :rules="rules"   :required="required"
            @update:modelValue="emit('update:modelValue', $event)"
        >
        </v-select>
        <v-progress-circular v-if="loading" indeterminate size="24" />
        <v-alert v-if="error" type="error">{{ error }}</v-alert>
</template>
