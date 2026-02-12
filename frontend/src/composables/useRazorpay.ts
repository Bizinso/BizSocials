import { ref } from 'vue'

declare global {
  interface Window {
    Razorpay: new (options: RazorpayOptions) => RazorpayInstance
  }
}

interface RazorpayOptions {
  key: string
  subscription_id?: string
  amount?: number
  currency?: string
  name: string
  description?: string
  image?: string
  prefill?: {
    name?: string
    email?: string
    contact?: string
  }
  theme?: {
    color?: string
  }
  handler?: (response: RazorpayResponse) => void
  modal?: {
    ondismiss?: () => void
  }
}

interface RazorpayInstance {
  open(): void
  close(): void
}

export interface RazorpayResponse {
  razorpay_payment_id: string
  razorpay_subscription_id?: string
  razorpay_signature: string
}

export function useRazorpay() {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const scriptLoaded = ref(false)

  async function loadScript(): Promise<void> {
    if (scriptLoaded.value || window.Razorpay) {
      scriptLoaded.value = true
      return
    }

    return new Promise((resolve, reject) => {
      const script = document.createElement('script')
      script.src = 'https://checkout.razorpay.com/v1/checkout.js'
      script.async = true
      script.onload = () => {
        scriptLoaded.value = true
        resolve()
      }
      script.onerror = () => reject(new Error('Failed to load Razorpay script'))
      document.head.appendChild(script)
    })
  }

  async function openCheckout(options: {
    subscriptionId?: string
    amount?: number
    currency?: string
    name?: string
    description?: string
    prefill?: { name?: string; email?: string }
    onSuccess: (response: RazorpayResponse) => void
    onDismiss?: () => void
  }): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await loadScript()

      const keyId = import.meta.env.VITE_RAZORPAY_KEY_ID

      if (!keyId) {
        throw new Error('Razorpay key not configured')
      }

      const rzpOptions: RazorpayOptions = {
        key: keyId,
        name: options.name ?? 'BizSocials',
        description: options.description ?? 'Subscription Payment',
        theme: { color: '#4F46E5' },
        handler: (response) => {
          loading.value = false
          options.onSuccess(response)
        },
        modal: {
          ondismiss: () => {
            loading.value = false
            options.onDismiss?.()
          },
        },
      }

      if (options.subscriptionId) {
        rzpOptions.subscription_id = options.subscriptionId
      }

      if (options.amount) {
        rzpOptions.amount = options.amount
        rzpOptions.currency = options.currency ?? 'INR'
      }

      if (options.prefill) {
        rzpOptions.prefill = options.prefill
      }

      const rzp = new window.Razorpay(rzpOptions)
      rzp.open()
    } catch (e) {
      loading.value = false
      error.value = e instanceof Error ? e.message : 'Failed to open checkout'
      throw e
    }
  }

  return {
    loading,
    error,
    scriptLoaded,
    loadScript,
    openCheckout,
  }
}
