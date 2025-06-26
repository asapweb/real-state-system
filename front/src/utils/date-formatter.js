// src/utils/date-formatter.js
import { format, getYear, getMonth, getDate } from 'date-fns' // Librería popular para manipulación de fechas

export function formatDate(date, formatString = 'yyyy-MM-dd') {
  if (!date) {
    return ''
  }
  try {
    return format(new Date(date), formatString)
  } catch (error) {
    console.error('Error formatting date:', error)
    return ''
  }
}

export function formatDateTime(date) {
  if (!date || isNaN(new Date(date).getTime())) {
    return ''
  }

  const dateToFormat = new Date(date)
  return format(dateToFormat, 'd MMM yyyy')
}

/* Esta funcion formatea una fecha para mostrarla de manera amigable,
 * dependiendo de si es el mismo día, el mismo año o un año diferente.
 */
export function formatReducedDateTime(date) {
  if (!date) {
    return ''
  }

  const currentDate = new Date()
  const currentYear = getYear(currentDate)
  const currentMonth = getMonth(currentDate) // 0-indexed
  const currentDay = getDate(currentDate)

  const dateToFormat = new Date(date)
  const dateYear = getYear(dateToFormat)
  const dateMonth = getMonth(dateToFormat) // 0-indexed
  const dateDay = getDate(dateToFormat)

  if (currentYear === dateYear && currentMonth === dateMonth && currentDay === dateDay) {
    return format(dateToFormat, 'HH:mm')
  } else if (currentYear === dateYear) {
    return format(dateToFormat, 'dd/MM HH:mm')
  } else {
    return format(dateToFormat, 'dd/MM/yyyy HH:mm')
  }
}

// ... otras funciones de formato
