import { defineStore } from 'pinia'
import axios from '@/services/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authUser: null,
    authToken: null,
  }),
  getters: {
    user: (state) => state.authUser,
    token: (state) => state.authTser,
  },
  actions: {
    async getToken() {
      //   console.log('getToken')
      //   await axios.get('/sanctum/csrf-cookie')
    },
    async login(form) {
      await this.getToken()
      await axios
        .post('api/login', form)
        .then((res) => {
          axios.defaults.headers.common['Authorization'] = 'Bearer ' + res.data.token
          this.authToken = res.data.token
          this.authUser = res.data.data
          this.router.push('')
        })
        .catch((errors) => {
          console.log(errors)
        })
    },

    async register(form) {
      //   await this.getToken()
      console.log('register')
      await axios
        .post('/api/auth/register', form)
        .then((res) => {
          alert(res.data.message)
          setTimeout(() => this.router.push('login'), 2000)
        })
        .catch((errors) => {
          let desc = ''
          errors.response.data.errors.map((e) => {
            desc = desc + ' ' + e
          })
          alert(desc)
        })
    },
    async logout() {
      console.log('log aut???')
      console.log('log aut axios ???')
      await axios.post('api/logout', this.authToken)
      console.log('log aut???')
      this.authToken = null
      this.authUser = null
      axios.defaults.headers.common['Authorization'] = null
      console.log('log aut pre route push???')
      this.router.push('/login')
      console.log('log aut pre route push???')
    },
  },
  persist: true,
})
