// services/dateService.js
import moment from 'moment-timezone'

moment.tz.setDefault('America/Argentina/Buenos_Aires')

export default {
  formatDate(date, format = 'LLLL') {
    return moment(date).format(format)
  },

  calculateAge(birthDate) {
    return moment().diff(birthDate, 'years')
  },

  // ... otros métodos relacionados con fechas
}
