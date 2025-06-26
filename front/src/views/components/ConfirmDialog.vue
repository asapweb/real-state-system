<template>
  <v-dialog v-model="isOpen" max-width="500px">
    <v-card :title="title">
      <v-card-text>
        <div v-html="message"></div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="cancel">Cancelar</v-btn>
        <v-btn :color="confirmColor" :loading="loading" @click="confirm">
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  title: { type: String, default: '¿Estás seguro?' },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Confirmar' },
  confirmColor: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const isOpen = ref(props.modelValue)

watch(
  () => props.modelValue,
  (val) => {
    isOpen.value = val
  },
)
watch(isOpen, (val) => {
  emit('update:modelValue', val)
})

const confirm = () => emit('confirm')
const cancel = () => emit('cancel')
</script>
