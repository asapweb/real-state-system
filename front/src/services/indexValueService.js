import axios from './axios'

class IndexValueService {
  /**
   * Obtener lista de valores de índice con filtros
   */
  async getIndexValues(params = {}) {
    try {
      const response = await axios.get('/api/index-values', { params })
      return response.data
    } catch (error) {
      console.error('Error fetching index values:', error)
      throw error
    }
  }

  /**
   * Obtener un valor de índice específico
   */
  async getIndexValue(id) {
    try {
      const response = await axios.get(`/api/index-values/${id}`)
      return response.data
    } catch (error) {
      console.error('Error fetching index value:', error)
      throw error
    }
  }

  /**
   * Crear un nuevo valor de índice
   */
  async createIndexValue(data) {
    try {
      const response = await axios.post('/api/index-values', data)
      return response.data
    } catch (error) {
      console.error('Error creating index value:', error)
      throw error
    }
  }

  /**
   * Actualizar un valor de índice
   */
  async updateIndexValue(id, data) {
    try {
      const response = await axios.put(`/api/index-values/${id}`, data)
      return response.data
    } catch (error) {
      console.error('Error updating index value:', error)
      throw error
    }
  }

  /**
   * Eliminar un valor de índice
   */
  async deleteIndexValue(id) {
    try {
      const response = await axios.delete(`/api/index-values/${id}`)
      return response.data
    } catch (error) {
      console.error('Error deleting index value:', error)
      throw error
    }
  }

  /**
   * Obtener valores por modo de cálculo
   */
  async getByCalculationMode(mode, filters = {}) {
    try {
      const response = await axios.get(`/api/index-values/by-mode/${mode}`, { params: filters })
      return response.data
    } catch (error) {
      console.error('Error fetching values by calculation mode:', error)
      throw error
    }
  }

  /**
   * Obtener el último valor para un tipo de índice
   */
  async getLatestValue(indexTypeId) {
    try {
      const response = await axios.get(`/api/index-values/latest/${indexTypeId}`)
      return response.data
    } catch (error) {
      console.error('Error fetching latest value:', error)
      throw error
    }
  }

  /**
   * Obtener tipos de índice
   */
  async getIndexTypes() {
    try {
      const response = await axios.get('/api/index-types')
      return response.data
    } catch (error) {
      console.error('Error fetching index types:', error)
      throw error
    }
  }

  /**
   * Formatear valor de índice para mostrar
   */
  formatIndexValue(indexValue) {
    if (!indexValue) return '—'
    if (indexValue.effective_date) {
      const date = new Date(indexValue.effective_date)
      return `${date.toLocaleDateString('es-ES')} (${indexValue.value}${indexValue.calculation_mode === 'percentage' ? '%' : ''})`
    }
    return '—'
  }

  /**
   * Formatear fecha para mostrar
   */
  formatDate(date) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('es-ES')
  }

  /**
   * Validar datos
   */
  validateData(data) {
    const errors = []
    if (!data.index_type_id) {
      errors.push('El tipo de índice es requerido')
    }
    if (!data.effective_date) {
      errors.push('La fecha efectiva es requerida')
    } else {
      // Validar formato YYYY-MM-DD
      if (!/^\d{4}-\d{2}-\d{2}$/.test(data.effective_date)) {
        errors.push('La fecha debe tener formato YYYY-MM-DD')
      }
    }
    if (data.value === undefined || data.value === null || data.value === '') {
      errors.push('El valor es requerido')
    } else if (isNaN(data.value) || data.value < 0) {
      errors.push('El valor debe ser un número positivo')
    }
    return errors
  }

  /**
   * Preparar datos para envío
   */
  prepareDataForSubmission(data, frequency) {
    let effectiveDate = data.effective_date
    if (frequency === 'monthly' && effectiveDate) {
      const d = new Date(effectiveDate)
      effectiveDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
    }
    return {
      index_type_id: data.index_type_id,
      effective_date: effectiveDate,
      value: data.value,
    }
  }
}

export default new IndexValueService()
