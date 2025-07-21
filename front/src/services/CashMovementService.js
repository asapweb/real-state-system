import axios from './axios';

const CashMovementService = {
  getCashMovements(params) {
    return axios.get('/api/cash-movements', { params });
  },
  createCashMovement(payload) {
    return axios.post('/api/cash-movements', payload);
  },
  deleteCashMovement(id) {
    return axios.delete(`/api/cash-movements/${id}`);
  },
};

export default CashMovementService;
