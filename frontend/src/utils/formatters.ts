import dayjs from 'dayjs'

export function formatDate(date: string | null | undefined, format = 'MMM D, YYYY'): string {
  if (!date) return '—'
  return dayjs(date).format(format)
}

export function formatDateTime(date: string | null | undefined): string {
  if (!date) return '—'
  return dayjs(date).format('MMM D, YYYY h:mm A')
}

export function formatRelative(date: string | null | undefined): string {
  if (!date) return '—'
  return dayjs(date).fromNow()
}

export function formatCurrency(amount: string | number, currency = 'INR'): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(num)
}

export function formatNumber(num: number): string {
  if (num >= 1_000_000) return `${(num / 1_000_000).toFixed(1)}M`
  if (num >= 1_000) return `${(num / 1_000).toFixed(1)}K`
  return num.toString()
}

export function truncate(text: string, length = 100): string {
  if (text.length <= length) return text
  return text.slice(0, length) + '...'
}

export function initials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
