<template>
  <div class="date-picker-custom">
    <v-text-field
      v-model="formattedDate"
      label="Seleccionar fecha"
      @focus="showDatePicker = true"
      @blur="formatDate"
    ></v-text-field>
    <v-date-picker
      v-model="date"
      :show="showDatePicker"
      @input="updateFormattedDate"
      @cancel="showDatePicker = false"
    ></v-date-picker>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const date = ref(null)
const formattedDate = ref('')
const showDatePicker = ref(false)

const formatDate = () => {
  if (!date.value) {
    formattedDate.value = ''
    return
  }
  formattedDate.value = date.value.toLocaleDateString('es-ES')
}

const updateFormattedDate = (newDate) => {
  date.value = newDate
  formatDate()
  showDatePicker.value = false
}

onMounted(() => {
  formatDate()
})
</script>

<style scoped>
.date-picker-custom {
  position: relative; /* Para que el date-picker se posicione correctamente */
}
</style>
