import axios from 'axios'
// import echo from './sockets'
import { useAuthStore } from '@/stores/auth'
import router from '../router'

const axiosInstance = axios

axiosInstance.defaults.baseURL = import.meta.env.VITE_API_URL
axiosInstance.defaults.headers.common['accept'] = 'application/json'
axiosInstance.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Interceptores
axiosInstance.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  const token = authStore.authToken

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

axiosInstance.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    const authStore = useAuthStore()
    if (error.response && error.response.status === 401) {
      const originalRequest = error.config

      if (originalRequest.url === 'api/logout') {
        router.push('/login')
        return Promise.reject(error) // Let the original logout error propagate
      }
      try {
        await authStore.logout() // Clear local storage and state
        router.push('/login')
      } catch (logoutError) {
        console.error('Error during logout in interceptor:', logoutError)
        return Promise.reject(logoutError) // Reject with logout error
      }
      // if (router.currentRoute.value.name !== 'Login') {
      //   authStore.logout()
      //   router.push('/login')
      // }
    }

    return Promise.reject(error)
  },
)

export default axiosInstance
