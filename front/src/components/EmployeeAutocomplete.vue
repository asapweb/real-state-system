<template>
  <v-autocomplete
    v-model="selectedEmployee"
    :items="employees"
    :label="label"
    clearable
    @update:search="onSearch"
    @update:modelValue="onEmployeeSelected"
    :auto-select-first="true"
    :loading="loading"
    return-object
    no-filter
  >
    <template #item="{ props, item }">
      <v-list-item
        v-bind="props"
        :subtitle="item.raw.document_number"
        :title="item.raw.name + ' ' + item.raw.last_name"
      ></v-list-item>
    </template>
    <template #selection="{ props, item }">
      <!-- ??? -->
      <!-- {{ item }} -->
      <span v-bind="props" v-if="item">
        {{ item.raw.name }} {{ item.raw.last_name }} - {{ item.raw.document_number }}
      </span>
    </template>
  </v-autocomplete>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, onMounted } from 'vue'
import { debounce } from 'lodash-es'

// Propiedades
const props = defineProps({
  employeeId: {
    type: [Number],
    default: null,
  },
  label: {
    type: [String],
    default: 'Empleado',
  },
})

const selectedEmployee = ref(null)
const employees = ref([])
const loading = ref(false)

// Eventos
const emit = defineEmits(['update:modelValue'])

const fetchEmployeeById = async (id) => {
  console.log('fetchEmployee')
  loading.value = true
  await axios
    .get('/api/clients/' + id)
    .then((response) => {
      let data = response.data
      data.fullName = `${response.data.name} ${response.data.last_name} - ${response.data.document_number}`
      employees.value = [data]
      selectedEmployee.value = data
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}

const fetchEmployees = async (query) => {
  if (!query) {
    employees.value = []
    return
  }

  loading.value = true
  await axios
    .get('/api/clients', {
      params: {
        search: { name: query },
      },
    })
    .then((response) => {
      employees.value = response.data.data.map((item) => ({
        ...item,
        fullName: `${item.name} ${item.last_name} - ${item.document_number}`,
      }))
      console.log(employees)
    })
    .catch((error) => {
      console.error(error)
      employees.value = []
    })
    .finally(() => {
      loading.value = false
    })
}

const onSearch = debounce((query) => {
  fetchEmployees(query)
}, 500)

const onEmployeeSelected = (aEmployee) => {
  emit('update:modelValue', aEmployee)
}

onMounted(() => {
  console.log('mounted')
  if (props.employeeId) {
    fetchEmployeeById(props.employeeId)
  }
})
</script>
