import { ref } from 'vue'
import axiosInstance from '@/services/axios'

export function useVouchers () {
  const loading = ref(false)
  const error = ref(null)

  /**
   * Obtiene los vouchers (COB y complementarios) de un contrato para un período YYYY-MM
   * @param {Number} contractId
   * @param {String} period
   */
  async function getContractVouchers (contractId, period) {
    console.log('getContractVouchers', contractId, period)
    try {
      loading.value = true
      const response = await axiosInstance.get(`/api/contracts/${contractId}/collections`, {
        params: { period }
      })
      return response
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  return { getContractVouchers, loading, error }
}
