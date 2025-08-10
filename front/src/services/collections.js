import axios from '@/services/axios'

/**
 * Servicio relacionado a cobranzas (collections) de contratos.
 * Endpoints backend manejados en VoucherController pero expuestos
 * semánticamente como /collections en la API.
 */

/**
 * Vista previa de los ítems a cobrar de un contrato para un período.
 * GET /contracts/{contract}/collections/preview?period=YYYY-MM
 * @param {number} contractId
 * @param {string} period (YYYY-MM)
 * @returns {Promise<Object>} listado de VoucherItems
 */
export async function previewCobranzaForContract (contractId, period) {
  const { data } = await axios.get(`/api/contracts/${contractId}/collections/preview`, {
    params: { period }
  })
  return data
}

/**
 * Genera (o actualiza) la cobranza mensual (voucher COB) para un contrato.
 * POST /contracts/{contract}/collections/generate
 * @param {number} contractId
 * @param {string} period (YYYY-MM)
 * @returns {Promise<Object>} voucher generado
 */
export async function generateCobranzaForContract (contractId, period) {
  const { data } = await axios.post(`/api/contracts/${contractId}/collections/generate`, {
    period
  })
  return data
}
