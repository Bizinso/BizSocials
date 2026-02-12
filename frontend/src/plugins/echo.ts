import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Pusher is required by Laravel Echo even when using Reverb
;(window as unknown as Record<string, unknown>).Pusher = Pusher

let echoInstance: Echo<'reverb'> | null = null

export function createEcho(token: string): Echo<'reverb'> {
  if (echoInstance) {
    echoInstance.disconnect()
  }

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 6001),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 6001),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })

  return echoInstance
}

export function getEcho(): Echo<'reverb'> | null {
  return echoInstance
}

export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}
