export function formatModelId(id, prefix) {
  if (isNaN(Number(id))) return '—'

  const formattedId = String(id).padStart(8, '0')
  const formattedPrefix = prefix ? `${prefix}-` : ''
  return `${formattedPrefix}${formattedId}`
}
