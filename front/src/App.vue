<template>
  <div id="app">
    <router-view :key="$route.path" />

    <div class="snackbar-stack">
      <v-snackbar
        v-for="item in queue"
        :key="item.id"
        v-model="visibility[item.id]"
        :color="item.color"
        timeout="3000"
        location="top right"
        @update:modelValue="(val) => onClose(val, item.id)"
      >
        {{ item.message }}
      </v-snackbar>
    </div>
  </div>
</template>

<script setup>
  import { RouterView } from 'vue-router'
  import { reactive, watch } from 'vue'
import { useSnackbar } from '@/composables/useSnackbar'

const { queue } = useSnackbar()

// Estado de visibilidad por ID
const visibility = reactive({})

// Cada vez que cambia el queue, activar visibilidad
watch(
  queue,
  (newQueue) => {
    newQueue.forEach((item) => {
      if (!(item.id in visibility)) {
        visibility[item.id] = true
      }
    })
  },
  { deep: true }
)

const onClose = (val, id) => {
  if (!val) {
    setTimeout(() => {
      const index = queue.value.findIndex((q) => q.id === id)
      if (index !== -1) queue.value.splice(index, 1)
      delete visibility[id]
    }, 200)
  }
}
</script>
