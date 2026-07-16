const dateFormatter = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' })
const dateTimeFormatter = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })

export const formatDate = (value) => value ? dateFormatter.format(new Date(value)) : '—'

export const formatDateTime = (value) => value ? dateTimeFormatter.format(new Date(value)) : '—'
