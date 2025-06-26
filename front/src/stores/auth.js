// src/stores/auth.js
import { defineStore } from 'pinia'
import axios from '@/services/axios'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore(
  'auth',
  () => {
    // Estado reactivo
    const authUser = ref(null)
    const authToken = ref(null)
    const authRoles = ref([]) // Nuevo estado para los roles
    const authPermissions = ref([]) // Nuevo estado para los permisos

    // Getters
    const isAuthenticated = computed(() => !!authToken.value)
    const user = computed(() => authUser.value)
    const roles = computed(() => authRoles.value) // Nuevo getter para los roles
    const permissions = computed(() => authPermissions.value) // Nuevo getter para los permisos

    // Acciones
    const login = async (credentials) => {
      try {
        // Hacer la solicitud de login
        const response = await axios.post('api/login', credentials)

        // Guardar el token y los datos del usuario, roles y permisos
        authToken.value = response.data.token
        authUser.value = response.data.data
        authRoles.value = response.data.roles || [] // Asume que la API devuelve un array de roles
        authPermissions.value = response.data.permissions || [] // Asume que la API devuelve un array de permisos

        // Configurar el token en las cabeceras de axios
        axios.defaults.headers.common['Authorization'] = `Bearer ${authToken.value}`

        return true // Éxito
      } catch (error) {
        console.error('Error during login:', error)
        throw error // Propagar el error
      }
    }

    const logout = async () => {
      try {
        // Hacer la solicitud de logout
        await axios.post('api/logout')

        // Limpiar el estado
        authToken.value = null
        authUser.value = null
        authRoles.value = [] // Limpiar roles al cerrar sesión
        authPermissions.value = [] // Limpiar permisos al cerrar sesión
        delete axios.defaults.headers.common['Authorization']

        return true // Éxito
      } catch (error) {
        console.error('Error during logout:', error)
        throw error // Propagar el error
      }
    }

    const hasRole = (role) => {
      return authRoles.value.includes(role)
    }

    const checkPermission = (permission) => {
      return authPermissions.value.includes(permission)
    }

    const hasAnyRole = (rolesArray) => {
      return rolesArray.some(role => authRoles.value.includes(role))
    }

    const checkAnyPermission = (permissionsArray) => {
      return permissionsArray.some(permission => authPermissions.value.includes(permission))
    }

    // Persistir el estado
    return {
      authUser,
      authToken,
      authRoles, // Exponer el estado de roles para la persistencia
      authPermissions, // Exponer el estado de permisos para la persistencia
      isAuthenticated,
      user,
      roles, // Exponer el getter de roles
      permissions, // Exponer el getter de permisos
      login,
      logout,
      hasRole,
      checkPermission,
      hasAnyRole,
      checkAnyPermission,
    }
  },
  {
    persist: true, // Habilita la persistencia del store
  },
)
