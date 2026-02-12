export const APP_NAME = import.meta.env.VITE_APP_NAME || 'BizSocials'

export const AUTH_TOKEN_KEY = 'auth_token'
export const ADMIN_TOKEN_KEY = 'admin_token'

export const NOTIFICATION_POLL_INTERVAL = 30_000 // 30 seconds

export const DEFAULT_PER_PAGE = 15

export const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB

export const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
export const SUPPORTED_VIDEO_TYPES = ['video/mp4', 'video/quicktime', 'video/webm']

export const SOCIAL_PLATFORM_COLORS: Record<string, string> = {
  linkedin: '#0A66C2',
  facebook: '#1877F2',
  instagram: '#E4405F',
  twitter: '#1DA1F2',
}

export const SOCIAL_PLATFORM_LABELS: Record<string, string> = {
  linkedin: 'LinkedIn',
  facebook: 'Facebook',
  instagram: 'Instagram',
  twitter: 'X (Twitter)',
}
