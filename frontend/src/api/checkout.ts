import { post } from './client'

export interface CheckoutData {
  subscription_id: string
  razorpay_subscription_id: string
  razorpay_key_id: string
  plan_name: string
  amount: number
  currency: string
}

export interface VerifyPaymentPayload {
  razorpay_payment_id: string
  razorpay_subscription_id: string
  razorpay_signature: string
}

export const checkoutApi = {
  initiate: (planId: string, billingCycle: string = 'monthly') =>
    post<CheckoutData>('/billing/checkout/initiate', { plan_id: planId, billing_cycle: billingCycle }),

  verify: (payload: VerifyPaymentPayload) =>
    post<any>('/billing/checkout/verify', payload),
}
