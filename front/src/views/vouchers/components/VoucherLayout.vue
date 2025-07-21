<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-toolbar flat color="transparent" class="px-0">
          <v-toolbar-title class="text-h6">{{ title }}</v-toolbar-title>
          <v-spacer />
          <slot name="actions" />
        </v-toolbar>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <slot />
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
const props = defineProps({
  title: { type: String, required: true }
})
</script>
