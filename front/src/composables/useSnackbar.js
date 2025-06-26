import { ref } from 'vue'

const queue = ref([])

export function useSnackbar() {
  function push(msg, type = 'info') {
    queue.value.push({
      id: Date.now() + Math.random(),
      message: msg,
      color: type,
    })
  }

  return {
    queue,
    success: (msg) => push(msg, 'success'),
    error: (msg) => push(msg, 'error'),
    info: (msg) => push(msg, 'info'),
    warning: (msg) => push(msg, 'warning'),
  }
}
