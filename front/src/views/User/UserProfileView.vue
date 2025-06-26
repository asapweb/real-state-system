<template>
  <div class="mt-3 mb-10">
    <h2 class="">Configuración</h2>
  </div>

  <v-card rounded="md" class="" variant="flat">
    <v-list lines="one">
      <v-list-item
        lines="two"
        title="Usuarios"
        subtitle="Crear y gestionar los usuarios"
        to="/settings/users"
        ><template v-slot:append>
          <v-btn color="grey-lighten-1" icon="mdi-chevron-right" variant="text"></v-btn> </template
      ></v-list-item>
      <v-list-item
        title="Departamentos"
        subtitle="Crear y gestionar los departamentos"
        to="/settings/departments"
        ><template v-slot:append>
          <v-btn color="grey-lighten-1" icon="mdi-chevron-right" variant="text"></v-btn> </template
      ></v-list-item>
    </v-list>
  </v-card>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { sendRequest } from '@/functions'
import clientForm from '@/components/ClientForm.vue'

// Server table
const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Departamentos', key: 'departments', sortable: false },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
]
const clientes = ref([])
const tablePage = ref(1)
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

const formDialog = ref(false)
const usuarioSeleccionado = ref(null)

let itemToDelete = null
const deleteDialog = ref(false)

const showFormDialog = (aClient) => {
  usuarioSeleccionado.value = aClient
  formDialog.value = true
}
const showDeleteDialog = (aClient) => {
  itemToDelete = aClient
  deleteDialog.value = true
}

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

const deleteClient = async () => {
  let res = await sendRequest('DELETE', '', 'api/clients/' + itemToDelete.id, '', '')
  if (res) {
    deleteDialog.value = false
    fetchClients()
  }
}

const onSaved = () => {
  fetchClients()
  formDialog.value = false
}
</script>
