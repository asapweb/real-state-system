<template>
  <div class="mb-8">
    <h2 class="">Visitas</h2>
    <small><a class="text-decoration-none text-primary " href="visits">Ver todas las visitas</a></small>
  </div>
  <v-row class="">
    <v-col class="" cols="12" lg="5"  sm="5">
      <v-text-field
      clearable
        variant="solo-filled"
        placeholder="Buscar"
        class="elevation-0"
        hide-details
        v-model="filter.text"
      ></v-text-field>
    </v-col>
    <v-col class="" cols="12" lg="7"  sm="7">
      <DepartmentAutocomplete multiple="true" label="Departemento" v-model="filter.department_id" />
    </v-col>
  </v-row>
  <v-row height="100%">
    <v-col cols="12" sm="6" md="4" height="100%">
      <visits-list :filters="filter" title="En sala de espera" status="arrived" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <visits-list :filters="filter" title="En Curso" status="attended" />
    </v-col>
    <v-col cols="12" sm="6" md="4">
      <visits-list :filters="filter" title="Finalizadas" status="seen" />
    </v-col>
  </v-row>
</template>

<script setup>
import DepartmentAutocomplete from '@/components/DepartmentAutocomplete.vue'
import { ref } from 'vue'
import visitsList from '@/components/VisitsList.vue'

const filter = ref({ text: '', department_id: null, isDashboard: 1 })
</script>
