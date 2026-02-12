import { SocialPlatform } from '@/types/enums'

export interface PlatformConfig {
  label: string
  icon: string
  color: string
  maxChars: number
  supportsMedia: boolean
  supportsVideo: boolean
  supportsStories: boolean
}

export const platformConfigs: Record<SocialPlatform, PlatformConfig> = {
  [SocialPlatform.Linkedin]: {
    label: 'LinkedIn',
    icon: 'pi pi-linkedin',
    color: '#0A66C2',
    maxChars: 3000,
    supportsMedia: true,
    supportsVideo: true,
    supportsStories: false,
  },
  [SocialPlatform.Facebook]: {
    label: 'Facebook',
    icon: 'pi pi-facebook',
    color: '#1877F2',
    maxChars: 63206,
    supportsMedia: true,
    supportsVideo: true,
    supportsStories: true,
  },
  [SocialPlatform.Instagram]: {
    label: 'Instagram',
    icon: 'pi pi-instagram',
    color: '#E4405F',
    maxChars: 2200,
    supportsMedia: true,
    supportsVideo: true,
    supportsStories: true,
  },
  [SocialPlatform.Twitter]: {
    label: 'X (Twitter)',
    icon: 'pi pi-twitter',
    color: '#1DA1F2',
    maxChars: 280,
    supportsMedia: true,
    supportsVideo: true,
    supportsStories: false,
  },
  [SocialPlatform.Whatsapp]: {
    label: 'WhatsApp',
    icon: 'pi pi-whatsapp',
    color: '#25D366',
    maxChars: 4096,
    supportsMedia: true,
    supportsVideo: true,
    supportsStories: false,
  },
}

export function getPlatformConfig(platform: SocialPlatform): PlatformConfig {
  return platformConfigs[platform]
}

export function getPlatformLabel(platform: string): string {
  return platformConfigs[platform as SocialPlatform]?.label || platform
}

export function getPlatformColor(platform: string): string {
  return platformConfigs[platform as SocialPlatform]?.color || '#6b7280'
}
