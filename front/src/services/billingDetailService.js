// src/services/billingDetailService.js
import api from './axios'; // <--- ¡Importa desde tu axios.js!

export default {
  /**
   * Obtiene todos los detalles de facturación para un cliente específico.
   * GET /api/clients/{clientId}/billing-details
   * @param {number} clientId - El ID del cliente
   * @returns {Promise<Array>} - Una promesa que resuelve con un array de detalles de facturación
   */
  getBillingDetailsByClient(clientId) {
    return api.get(`/api/clients/${clientId}/billing-details`)
      .then(response => response.data);
  },

  /**
   * Crea un nuevo detalle de facturación para un cliente.
   * POST /api/clients/{clientId}/billing-details
   * @param {number} clientId - El ID del cliente al que pertenece el detalle
   * @param {Object} data - Los datos del nuevo detalle de facturación (billing_name, document_number, billing_address, is_default)
   * @returns {Promise<Object>} - Una promesa que resuelve con el detalle de facturación creado
   */
  createBillingDetail(clientId, data) {
    // Asegúrate de que 'data' solo contenga los campos de tu migración simplificada
    // (billing_name, document_type_id, document_number, billing_address, is_default, tax_condition_id)
    return api.post(`/api/clients/${clientId}/billing-details`, data)
      .then(response => response.data);
  },

  /**
   * Actualiza un detalle de facturación existente.
   * PUT/PATCH /api/clients/{clientId}/billing-details/{billingDetailId}
   * @param {number} clientId - El ID del cliente al que pertenece el detalle (necesario para la URL)
   * @param {number} billingDetailId - El ID del detalle de facturación a actualizar
   * @param {Object} data - Los datos actualizados del detalle de facturación
   * @returns {Promise<Object>} - Una promesa que resuelve con el detalle de facturación actualizado
   */
  updateBillingDetail(clientId, billingDetailId, data) {
    // Es CRUCIAL que clientId esté aquí para construir la URL correctamente.
    // Asegúrate de que 'data' solo contenga los campos de tu migración simplificada
    return api.put(`/api/clients/${clientId}/billing-details/${billingDetailId}`, data)
      .then(response => response.data);
  },

  /**
   * Elimina un detalle de facturación.
   * DELETE /api/clients/{clientId}/billing-details/{billingDetailId}
   * @param {number} clientId - El ID del cliente al que pertenece el detalle (necesario para la URL)
   * @param {number} billingDetailId - El ID del detalle de facturación a eliminar
   * @returns {Promise<void>} - Una promesa que resuelve cuando la eliminación es exitosa
   */
  deleteBillingDetail(clientId, billingDetailId) {
    // Es CRUCIAL que clientId esté aquí para construir la URL correctamente.
    return api.delete(`/api/clients/${clientId}/billing-details/${billingDetailId}`)
      .then(response => response.data);
  },
};
