<template>
  <div class="mb-8">
    <h2 class="">Configuración</h2>
  </div>

  <v-row>
    <v-col>
      <v-list lines="one">
        <v-list-item
          lines="two"
          title="Usuarios"
          subtitle="Crear y gestionar los usuarios"
          to="/settings/users"
        >
          <template v-slot:append
            ><v-btn color="grey-lighten-1" icon="mdi-chevron-right" variant="text"></v-btn>
          </template>
        </v-list-item>
        <v-list-item
          title="Roles y Permisos"
          subtitle="Gestionar los roles de usuario"
          to="/settings/roles"
        >
          <template v-slot:append
            ><v-btn color="grey-lighten-1" icon="mdi-chevron-right" variant="text"></v-btn>
          </template>
        </v-list-item>
        <v-list-item
          title="Departamentos"
          subtitle="Crear y gestionar los departamentos"
          to="/settings/departments"
        >
          <template v-slot:append
            ><v-btn color="grey-lighten-1" icon="mdi-chevron-right" variant="text"></v-btn>
          </template>
        </v-list-item>
      </v-list>
    </v-col>
  </v-row>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'

// Server table
const clientes = ref([])
const loading = ref(false)
const totalItems = ref(0)

const name = ref('')
const search = ref('')
const options = ref({})

watch(
  [name],
  () => {
    loading.value = true
    debouncedFetchClients()
    search.value = String(Date.now())
  },
  { deep: true },
)

const debouncedFetchClients = debounce(() => {
  fetchClients() // Reinicia siempre en la página 1 al filtrar
}, 500)

const fetchClients = async () => {
  loading.value = true
  const opt = options.value
  await axios
    .get('/api/users', {
      params: {
        page: opt.page,
        itemsPerPage: opt.itemsPerPage,
        sortBy: opt.sortBy,
        search: { name: name.value },
      },
    })
    .then((response) => {
      clientes.value = response.data.data
      totalItems.value = response.data.total
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}
</script>
