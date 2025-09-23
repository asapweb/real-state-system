// src/utils/date-formatter.js
import { format, parseISO, isValid, getYear, getMonth, getDate } from 'date-fns'

// -------- Helpers --------
const DATE_ONLY_REGEX = /^\d{4}-\d{2}-\d{2}$/

function isDateOnlyString(value) {
  return typeof value === 'string' && DATE_ONLY_REGEX.test(value)
}

/** Crea un Date local SIN desplazar por timezone a partir de 'YYYY-MM-DD' */
function dateFromDateOnly(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number)
  // new Date(año, mes 0-index, día) crea fecha local a medianoche sin offset
  return new Date(y, m - 1, d, 0, 0, 0, 0)
}

/** Normaliza distintos inputs a Date, respetando DATE-only sin shift */
function toDate(value) {
  if (!value) return null
  if (value instanceof Date) return isValid(value) ? value : null
  if (typeof value === 'number') {
    const d = new Date(value)
    return isValid(d) ? d : null
  }
  if (typeof value === 'string') {
    if (isDateOnlyString(value)) return dateFromDateOnly(value)
    // ISO / otras variantes
    const d = parseISO(value)
    return isValid(d) ? d : null
  }
  return null
}

// -------- API pública --------

export function formatPeriodWithMonthName(value) {
  const d = toDate(value)
  return d ? format(d, 'MMMM yyyy') : ''
}

/**
 * Formatea fecha. Acepta:
 * - 'YYYY-MM-DD' (DATE) => sin corrimiento de zona horaria
 * - ISO/DATETIME (string), timestamp (number) o Date
 */
export function formatDate(value, formatString = 'dd/MM/yyyy') {
  const d = toDate(value)
  return d ? format(d, formatString) : ''
}

export function formatDateTime(value) {
  const d = toDate(value)
  return d ? format(d, 'd MMM yyyy HH:mm') : ''
}

/**
 * Muestra:
 *  - HH:mm si es hoy
 *  - dd/MM HH:mm si es este año
 *  - dd/MM/yyyy HH:mm si es otro año
 */
export function formatReducedDateTime(value) {
  const d = toDate(value)
  if (!d) return ''

  const now = new Date()
  const sameDay =
    getYear(now) === getYear(d) &&
    getMonth(now) === getMonth(d) &&
    getDate(now) === getDate(d)

  if (sameDay) return format(d, 'HH:mm')
  if (getYear(now) === getYear(d)) return format(d, 'dd/MM HH:mm')
  return format(d, 'dd/MM/yyyy HH:mm')
}

/** Utilidades opcionales para ida/vuelta con la API */

// Convierte un Date (local) a 'YYYY-MM-DD' (para campos DATE de la BD)
export function toApiDate(value) {
  const d = toDate(value)
  if (!d) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// Convierte un Date/ISO/timestamp a ISO completo (ej. para DATETIME)
export function toApiDateTime(value) {
  const d = toDate(value)
  return d ? d.toISOString() : ''
}
