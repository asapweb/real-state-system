// src/services/clientService.js
import axios from './axios'

const clientService = {
  /**
   * Obtiene una lista de clients con filtros y paginación.
   * @param {Object} params - Objeto con los parámetros de la consulta (ej. { page: 1, search: 'Juan' }).
   * @returns {Promise<Object>} Promesa que resuelve en los datos de la respuesta.
   */
  async getClients(params = {}) {
    try {
      const response = await axios.get(`/api/clients`, { params })
      return response.data
    } catch (error) {
      console.error('Error al obtener clients:', error)
      throw error // Propagar el error para que el componente lo maneje
    }
  },

  /**
   * Obtiene un cliente específico por su ID.
   * @param {number} id - El ID del cliente.
   * @returns {Promise<Object>} Promesa que resuelve en los datos del cliente.
   */
  async getClient(id) {
    try {
      const response = await axios.get(`/api/clients/${id}`)
      return response.data
    } catch (error) {
      console.error(`Error al obtener cliente con ID ${id}:`, error)
      throw error
    }
  },

  /**
   * Crea un nuevo cliente.
   * @param {Object} clientData - Los datos del nuevo cliente.
   * @returns {Promise<Object>} Promesa que resuelve en los datos del cliente creado.
   */
  async createClient(clientData) {
    try {
      const response = await axios.post(`/api/clients`, clientData)
      return response.data
    } catch (error) {
      console.error('Error al crear cliente:', error)
      throw error
    }
  },

  /**
   * Actualiza un cliente existente.
   * @param {number} id - El ID del cliente a actualizar.
   * @param {Object} clientData - Los datos actualizados del cliente.
   * @returns {Promise<Object>} Promesa que resuelve en los datos del cliente actualizado.
   */
  async updateClient(id, clientData) {
    try {
      const response = await axios.put(`/api/clients/${id}`, clientData)
      return response.data
    } catch (error) {
      console.error(`Error al actualizar cliente con ID ${id}:`, error)
      throw error
    }
  },

  /**
   * Elimina un cliente.
   * @param {number} id - El ID del cliente a eliminar.
   * @returns {Promise<void>} Promesa que resuelve cuando el cliente ha sido eliminado.
   */
  async deleteClient(id) {
    try {
      await axios.delete(`/api/clients/${id}`)
    } catch (error) {
      console.error(`Error al eliminar cliente con ID ${id}:`, error)
      throw error
    }
  },
}

export default clientService
