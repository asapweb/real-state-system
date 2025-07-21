import axios from './axios'

export default {
  list(params) {
    return axios.get('/api/vouchers', { params })
  },
  get(id) {
    return axios.get(`/api/vouchers/${id}`)
  },
  create(data) {
    return axios.post('/api/vouchers', data)
  },
  update(id, data) {
    return axios.put(`/api/vouchers/${id}`, data)
  },
  remove(id) {
    return axios.delete(`/api/vouchers/${id}`)
  },
  issue(id) {
    return axios.post(`/api/vouchers/${id}/issue`)
  }
}
