<template>
  <v-container>
    <v-card class="pa-4" width="400">
      <v-form @submit.prevent="validate">
        <v-row>
          <v-col cols="12" md="5">
            <v-text-field
          v-model="name"
          :rules="nameRules"
          label="Nombre (requerido)"
          variant="outlined"
          density="compact"
          hide-details="auto"
        ></v-text-field>

          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12" md="5">

<v-text-field
class="mt-4"
label="Otro campo sin validación"
variant="outlined"
density="compact"
hide-details="auto"
></v-text-field>
</v-col>
</v-row>

        <v-btn type="submit" color="primary" class="mt-4">Validar</v-btn>
      </v-form>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';

const name = ref('');

const nameRules = [
  value => {
    if (value) return true;
    return 'El nombre es obligatorio.';
  },
];

// La función 'validate' es solo para el ejemplo,
// en una app real la validación de Vuetify es automática.
const validate = () => {
  console.log('Intento de validación');
};
</script>
