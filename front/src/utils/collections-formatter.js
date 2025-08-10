export function formatPeriod(period) {
  if (!period) return '—'
  const [year, month] = period.split('-')
  const date = new Date(Number(year), Number(month) - 1)
  return date.toLocaleString('es-AR', { month: 'long', year: 'numeric' })
}

export function formatStatus(status) {
  switch (status) {
    case 'pending':
      return 'Pendiente'
    case 'paid':
      return 'Pagada'
    case 'partially_paid':
      return 'Parcialmente Pagada'
    case 'canceled':
      return 'Cancelada'
    default:
      return status
  }
}

/**
 * Devuelve etiqueta legible para el estado de la cobranza.
 */
export function statusLabel(status) {
  switch (status) {
    case 'pending_adjustment':
      return 'Ajuste pendiente'
    case 'pending':
      return 'Pendiente'
    case 'draft':
      return 'Borrador'
    case 'issued':
      return 'Emitida'
    default:
      return status
  }
}

/**
 * Devuelve color para chip según estado.
 */
export function statusColor(status) {
  switch (status) {
    case 'pending_adjustment':
      return 'orange'
    case 'pending':
      return 'warning'
    case 'draft':
      return 'blue-grey'
    case 'issued':
      return 'success'
    default:
      return 'default'
  }
}
