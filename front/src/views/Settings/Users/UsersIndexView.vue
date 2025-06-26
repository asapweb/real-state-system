<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="$router.push('/settings')"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </v-btn>
    <h2 class="">Usuarios</h2>
  </div>
  <v-row>
    <v-col class="" cols="12" lg="5"  sm="5">
              <v-text-field
              clearable
        variant="solo-filled"
        placeholder="Buscar"
        class="elevation-0"
                v-model="name"
                hide-details
                single-line
              ></v-text-field>
            </v-col>

            <v-col class="text-right">
              <v-btn
                prepend-icon="mdi-plus"
                color="primary"
                variant="tonal"
                @click="showFormDialog(null)"
                >Agregar Usuario</v-btn
              >
            </v-col>
          </v-row>
  <v-row>
    <v-col>
          <v-data-table-server
            class="mt-4"
            v-model:options="options"
            :headers="headers"
            :items="clientes"
            :items-length="totalItems"
            :loading="loading"
            :page="tablePage"
            @update:options="fetchClients"
          >
            <template v-slot:loading>
              <v-skeleton-loader type="table-row@4"></v-skeleton-loader>
            </template>
            <template v-slot:item.name="{ item }">
                <a class="text-decoration-none text-primary " :href="`/settings/users/${ item.id }`">{{ item.name }}</a>
                </template>
            <template v-slot:item.departments="{ item }">
              <!-- {{ item.departments }} -->
              <div v-for="department in item.departments" :key="department.id">
                {{ department.name }}
              </div>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon class="me-2" size="small" @click="showFormDialog(item)"> mdi-pencil </v-icon>
              <v-icon size="small" @click="showDeleteDialog(item)"> mdi-delete </v-icon>
            </template>
          </v-data-table-server>
    </v-col>
  </v-row>
  <v-dialog v-model="deleteDialog" max-width="290">
    <v-card>
      <v-card-title class="headline">Confirmar eliminación</v-card-title>
      <v-card-text> ¿Está seguro que desea eliminar este registro? </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="deleteDialog = false"> Cancelar </v-btn>
        <v-btn color="red darken-1" text @click="deleteClient"> Eliminar </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog v-model="formDialog">
    <user-form :client="usuarioSeleccionado" @saved="onSaved" @close="formDialog = false" />
  </v-dialog>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { sendRequest } from '@/functions'
import userForm from '@/components/UserForm.vue'

// Server table
const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Email', key: 'email' },
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
  let res = await sendRequest('DELETE', '', 'api/users/' + itemToDelete.id, '', '')
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
