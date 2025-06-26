export function formatPeriod(period) {
  if (!period) return '—';
  const [year, month] = period.split('-');
  const date = new Date(Number(year), Number(month) - 1);
  return date.toLocaleString('es-AR', { month: 'long', year: 'numeric' });
}

export function formatStatus(status) {
  switch (status) {
    case 'pending':
      return 'Pendiente';
    case 'paid':
      return 'Pagada';
    case 'partially_paid':
      return 'Parcialmente Pagada';
    case 'canceled':
      return 'Cancelada';
    default:
      return status;
  }
}

export function statusColor(status) {
  switch (status) {
    case 'pending':
      return 'warning';
    case 'paid':
      return 'success';
    case 'partially_paid':
      return 'info';
    case 'canceled':
      return 'error';
    default:
      return 'default';
  }
}
