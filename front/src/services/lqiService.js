import axios from './axios'

function normalizeFilters(filters = {}) {
  const normalized = { ...filters }
  if (normalized.currency) {
    normalized.currency = normalized.currency.toUpperCase()
  }
  return normalized
}

export function fetchOverview(params = {}) {
  return axios.get('/api/lqi', { params: normalizeFilters(params) })
}

export function fetchKpis(params = {}) {
  return axios.get('/api/lqi/kpis', { params: normalizeFilters(params) })
}

export function generateBatch(payload = {}) {
  return axios.post('/api/lqi/generate', normalizeFilters(payload))
}

export function issueBatch(payload = {}) {
  return axios.post('/api/lqi/issue', normalizeFilters(payload))
}

export function reopenBatch(payload = {}) {
  const { reason, ...rest } = payload || {}
  const body = { ...normalizeFilters(rest) }
  if (reason) body.reason = reason
  return axios.post('/api/lqi/reopen', body)
}

export function postIssueAdjustments(contractId, period, currency) {
  return axios.post(`/api/lqi/${contractId}/${period}/${currency}/post-issue`)
}

export function postIssueAdjustmentsBulk(period, params = {}) {
  return axios.post(`/api/lqi/${period}/post-issue/bulk`, null, {
    params: normalizeFilters(params),
  })
}

export function syncLiquidation(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/sync`, payload)
}

export function issueLiquidation(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/issue`, payload)
}

export function reopenLiquidation(contractId, payload) {
  return axios.post(`/api/contracts/${contractId}/lqi/reopen`, payload)
}

export default {
  fetchOverview,
  fetchKpis,
  generateBatch,
  issueBatch,
  reopenBatch,
  syncLiquidation,
  issueLiquidation,
  reopenLiquidation,
  postIssueAdjustments,
  postIssueAdjustmentsBulk,
}
