<template>
  <div class="mb-8 d-flex align-center">
    <v-btn
      variant="text"
      class="mr-2 rounded-lg back-button"
      height="40"
      width="40"
      min-width="0"
      @click="$router.push('/settings/roles')"
    >
      <svg
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M15 18L9 12L15 6"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </v-btn>
    <h2>Roles y permisos</h2>
  </div>

  <v-row style="border-bottom: 1px solid #dedede">
    <v-col cols="12" sm="5" md="3">
      <h3 class="mb-0">Permisos del rol: {{ roleName }}</h3>
    </v-col>
    <v-col>
      <v-checkbox
        :model-value="allSelected"
        @update:model-value="toggleAllPermissions"
        :disabled="loadingPermissions"
        color="primary"
        density="comfortable"
        hide-details
      >
        <template #label>
          <div>
            <div class="font-weight-medium">Seleccionar todos</div>
            <div class="text-caption text-grey">Marcar o desmarcar todos los permisos</div>
          </div>
        </template>
      </v-checkbox>
    </v-col>
  </v-row>

  <!-- Permisos agrupados -->
  <template v-for="(permissions, groupName) in groupedPermissions" :key="groupName">
    <v-row style="border-bottom: 1px solid #dedede">
      <v-col cols="12" sm="5" md="3">
        <h4 class="mb-0 ml-2">{{ groupName }}</h4>
      </v-col>
      <v-col>
        <div>
          <v-checkbox
            class="mt-0 mb-2"
            :model-value="isGroupSelected(groupName)"
            @update:model-value="toggleGroupPermissions(groupName, $event)"
            :disabled="loadingPermissions"
            density="comfortable"
            hide-details
            color="primary"
          >
            <template #label>
              <div>
                <div class="font-weight-medium">Seleccionar todos</div>
                <div class="text-caption text-grey">
                  Marcar o desmarcar todos los permisos de {{ groupName }}
                </div>
              </div>
            </template></v-checkbox
          >
        </div>
        <div v-for="permission in permissions" :key="permission.name">
          <v-checkbox
            :model-value="selectedPermissions.includes(permission.name)"
            @update:model-value="togglePermission(permission.name)"
            :disabled="loadingPermissions"
            color="primary"
            density="comfortable"
            hide-details
          >
            <template #label>
              <div>
                <div class="font-weight-medium">{{ permission.name }}</div>
                <div class="text-caption text-grey">
                  {{ permission.description || 'Sin descripción.' }}
                </div>
              </div>
            </template>
          </v-checkbox>
        </div>
      </v-col>
    </v-row>
  </template>

  <v-row>
    <v-col>
      <div v-if="availablePermissions.length > 0"></div>

      <div v-else-if="loadingPermissions">
        <v-progress-circular indeterminate color="primary" class="me-2" />
        Cargando permisos...
      </div>

      <div v-else-if="errorPermissions">
        Error al cargar los permisos. Por favor, intente de nuevo.
      </div>

      <div v-else>No hay permisos disponibles en el sistema.</div>
    </v-col>
  </v-row>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/services/axios'

const route = useRoute()
const roleId = ref(route.params.id)

const emit = defineEmits(['permissions-updated'])

const availablePermissions = ref([])
const selectedPermissions = ref([]) // Almacena solo los NOMBRES de los permisos asignados
const loadingPermissions = ref(false)
const errorPermissions = ref(false)
const roleName = ref('')

const allSelected = computed(() => {
  return (
    availablePermissions.value.length > 0 &&
    availablePermissions.value.every((p) => selectedPermissions.value.includes(p.name))
  )
})
const isGroupSelected = (groupName) => {
  const group = groupedPermissions.value[groupName] || []
  return group.every((p) => selectedPermissions.value.includes(p.name))
}

const toggleAllPermissions = (value) => {
  if (value) {
    selectedPermissions.value = availablePermissions.value.map((p) => p.name)
  } else {
    selectedPermissions.value = []
  }
  updateRolePermissions()
}
const toggleGroupPermissions = (groupName, value) => {
  const group = groupedPermissions.value[groupName] || []
  const names = group.map((p) => p.name)

  if (value) {
    // Agregar los permisos del grupo que no estén ya seleccionados
    selectedPermissions.value = [...new Set([...selectedPermissions.value, ...names])]
  } else {
    // Eliminar los permisos del grupo
    selectedPermissions.value = selectedPermissions.value.filter((name) => !names.includes(name))
  }

  updateRolePermissions()
}

const groupedPermissions = computed(() => {
  const groups = {}
  for (const perm of availablePermissions.value) {
    const groupName = perm.group || 'Sin grupo'
    if (!groups[groupName]) {
      groups[groupName] = []
    }
    groups[groupName].push(perm)
  }
  return groups
})

const fetchPermissions = async () => {
  loadingPermissions.value = true
  errorPermissions.value = false
  try {
    const response = await axios.get('/api/permissions')
    availablePermissions.value = response.data.data // Asume que /api/permissions devuelve directamente el array de permisos
    console.log(availablePermissions.value)
  } catch (error) {
    console.error('Error al cargar todos los permisos:', error)
    errorPermissions.value = true
  } finally {
    loadingPermissions.value = false
  }
}

const fetchRolePermissions = async () => {
  loadingPermissions.value = true
  errorPermissions.value = false
  try {
    const response = await axios.get(`/api/roles/${roleId.value}/permissions`)
    selectedPermissions.value = response.data.data.map((perm) => perm.name) // Mapeamos a solo los nombres
  } catch (error) {
    console.error('Error al cargar los permisos del rol:', error)
    errorPermissions.value = true
  } finally {
    loadingPermissions.value = false
  }
}

const togglePermission = (permissionName) => {
  if (selectedPermissions.value.includes(permissionName)) {
    // Si ya está seleccionado, lo quitamos
    selectedPermissions.value = selectedPermissions.value.filter((name) => name !== permissionName)
  } else {
    // Si no está seleccionado, lo añadimos
    selectedPermissions.value.push(permissionName)
  }
  // Llamar a la función para actualizar en el backend inmediatamente
  updateRolePermissions()
}

const updateRolePermissions = async () => {
  try {
    // Enviamos solo los nombres de los permisos seleccionados
    const response = await axios.put(`/api/roles/${roleId.value}/permissions`, {
      permissions: selectedPermissions.value,
    })
    if (response.status === 200) {
      emit('permissions-updated', selectedPermissions.value)
      // Opcional: Mostrar un Snackbar de éxito
      console.log('Permisos del rol actualizados!')
    } else {
      console.error('Error al actualizar los permisos del rol:', response)
      // Opcional: Mostrar un Snackbar de error
    }
  } catch (error) {
    console.error('Error en la petición de actualización de permisos:', error)
    // Opcional: Mostrar un Snackbar de error
  }
}
const fetchRoleDetails = async () => {
  try {
    const response = await axios.get(`/api/roles/${roleId.value}`)
    roleName.value = response.data.data.data.name // Asume que la API devuelve un objeto con propiedad 'name'
  } catch (error) {
    console.error('Error al cargar los detalles del rol:', error)
    roleName.value = 'Rol desconocido'
  }
}

onMounted(async () => {
  // Cargar todos los permisos disponibles y los permisos del rol al inicio
  await fetchRoleDetails()
  await Promise.all([fetchPermissions(), fetchRolePermissions()])
})
</script>
