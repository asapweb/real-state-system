import axios from './axios'

function normalizePeriod(period) {
  if (!period) return undefined
  if (/^\d{4}-\d{2}$/.test(period)) {
    return `${period}-01`
  }
  return period
}

export function fetchLiquidations(params = {}) {
  const { period, ...rest } = params
  const query = {
    voucher_type: 'LQI',
    ...rest,
  }

  const normalizedPeriod = normalizePeriod(period)
  if (normalizedPeriod) {
    query.period = normalizedPeriod
  }

  return axios.get('/api/vouchers', { params: query })
}

export function syncLiquidations(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/sync`, payload)
}

export function issueLiquidations(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/issue`, payload)
}

export function reopenLiquidations(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/reopen`, payload)
}

export default {
  fetchLiquidations,
  syncLiquidations,
  issueLiquidations,
  reopenLiquidations,
}
