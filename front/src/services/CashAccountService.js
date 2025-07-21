import axios from './axios';

const CashAccountService = {
  getCashAccounts(params) {
    return axios.get('/api/cash-accounts', { params });
  },
  getActiveCashAccounts() {
    return axios.get('/api/cash-accounts/active');
  },
  getCashAccount(id) {
    return axios.get(`/api/cash-accounts/${id}`);
  },
  createCashAccount(payload) {
    return axios.post('/api/cash-accounts', payload);
  },
  updateCashAccount(id, payload) {
    return axios.put(`/api/cash-accounts/${id}`, payload);
  },
  deleteCashAccount(id) {
    return axios.delete(`/api/cash-accounts/${id}`);
  },
};

export default CashAccountService;
