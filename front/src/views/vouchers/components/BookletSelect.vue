<template>
  <v-select
    :model-value="modelValue"
    :items="booklets"
    item-title="name"
    item-value="id"
    label="Talonario"
    :loading="loading"
    clearable
    density="compact"
    return-object
    @update:modelValue="handleUpdate"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from '@/services/axios'

const props = defineProps({
  modelValue: {
    type: [Object, String, Number, null],
    default: null,
  },
  voucherType: { type: String, default: null },
  letter: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const booklets = ref([])
const loading = ref(false)

async function fetchBooklets() {
  loading.value = true
  try {
    const params = {}
    if (props.voucherType) params.type = props.voucherType
    if (props.letter) params.letter = props.letter

    const { data } = await axios.get('/api/booklets', { params })
    booklets.value = data.data || data
  } catch (e) {
    console.error('Error fetching booklets:', e)
  } finally {
    loading.value = false
  }
}

function handleUpdate(booklet) {
  emit('update:modelValue', booklet)
}

onMounted(fetchBooklets)

watch(
  () => [props.voucherType, props.letter],
  fetchBooklets
)
</script>
