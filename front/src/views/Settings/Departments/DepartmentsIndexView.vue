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
    <h2 class="">Departamentos</h2>
  </div>

      <v-row class="">
        <v-col>
          <v-text-field
          clearable
            variant="solo-filled"
            placeholder="Buscar"
            class="elevation-0"
            hide-details
            v-model="name"
          ></v-text-field>
        </v-col>

        <v-col class="text-right">
          <v-btn
            prepend-icon="mdi-plus"
            color="primary"
            variant="tonal"
            @click="showFormDialog(null)"
          >
            Departamento</v-btn
          >
        </v-col>
      </v-row>
      <v-row>
        <v-col>
          <v-data-table
          class="mt-4"
          v-model:options="options"
          :headers="headers"
          :items="departments"
          :items-length="totalItems"
          :loading="loading"
          :page="tablePage"
          @update:options="fetchDepartments"
          >
          <template v-slot:loading>
            <v-skeleton-loader type="table-row@4"></v-skeleton-loader>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-icon class="me-2" size="small" @click="showFormDialog(item)"> mdi-pencil </v-icon>
            <v-icon size="small" @click="showDeleteDialog(item)"> mdi-delete </v-icon>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

  <v-container>
    <v-dialog v-model="deleteDialog" max-width="290">
      <v-card>
        <v-card-title class="headline">Confirmar eliminación</v-card-title>
        <v-card-text> ¿Está seguro que desea eliminar este registro? </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="deleteDialog = false"> Cancelar </v-btn>
          <v-btn color="red darken-1" text @click="deleteDepartment"> Eliminar </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>

  <v-dialog v-model="formDialog">
    <department-form
      :department="usuarioSeleccionado"
      @saved="onSaved"
      @close="formDialog = false"
    />
  </v-dialog>

  <v-dialog>
    <v-form @submit.prevent="save">
      <v-card title="Departamento">
        <v-card-text>
          <v-row>
            <v-col>
              <v-text-field label="Nombre" v-model="form.name" type="text"></v-text-field>

              <v-btn color="primary" type="submit">Guardar</v-btn>
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

          <v-btn color="primary" text="Save" variant="tonal" type="submit">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
    {{ usuarioSeleccionado }}
  </v-dialog>
</template>

<script setup>
import axios from '@/services/axios'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { sendRequest } from '@/functions'
import departmentForm from '@/components/DepartmentForm.vue'

// Server table
const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Ubicación', key: 'location' },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
]

const departments = ref([])
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
    debouncedFetchDepartments()
    search.value = String(Date.now())
  },
  { deep: true },
)

const formDialog = ref(false)
const usuarioSeleccionado = ref(null)

let itemToDelete = null
const deleteDialog = ref(false)

const showFormDialog = (aDepartment) => {
  usuarioSeleccionado.value = aDepartment
  formDialog.value = true
}

const showDeleteDialog = (aDepartment) => {
  itemToDelete = aDepartment
  deleteDialog.value = true
}

const debouncedFetchDepartments = debounce(() => {
  fetchDepartments(1) // Reinicia siempre en la página 1 al filtrar
}, 500)

const fetchDepartments = async () => {
  loading.value = true
  const opt = options.value
  await axios
    .get('/api/departments', {
      params: {
        page: opt.page,
        itemsPerPage: opt.itemsPerPage,
        sortBy: opt.sortBy,
        search: { name: name.value },
      },
    })
    .then((response) => {
      console.log(response.data.data)
      departments.value = response.data.data
      totalItems.value = response.data.total
    })
    .catch((error) => {
      console.error(error)
    })
    .finally(() => {
      loading.value = false
    })
}

const deleteDepartment = async () => {
  let res = await sendRequest('DELETE', '', 'api/departments/' + itemToDelete.id, '', '')
  if (res) {
    deleteDialog.value = false
    fetchDepartments()
  }
}

const onSaved = () => {
  fetchDepartments()
  formDialog.value = false
}
</script>
