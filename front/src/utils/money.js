export function formatMoney(value, currency = '$') {
  if (isNaN(Number(value))) return '—'

  const number = Number(value)

  // Convertimos el número a formato local: 1.234,56
  const formatted = number.toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  return `${currency} ${formatted}`
}
