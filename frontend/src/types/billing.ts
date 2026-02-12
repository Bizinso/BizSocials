import type {
  SubscriptionStatus,
  BillingCycle,
  InvoiceStatus,
  PaymentMethodType,
  Currency,
} from './enums'

export interface SubscriptionData {
  id: string
  tenant_id: string
  plan_id: string
  plan_name: string
  status: SubscriptionStatus
  billing_cycle: BillingCycle
  currency: Currency
  amount: string
  current_period_start: string | null
  current_period_end: string | null
  trial_end: string | null
  is_on_trial: boolean
  trial_days_remaining: number
  days_until_renewal: number
  cancel_at_period_end: boolean
  cancelled_at: string | null
  created_at: string
}

export interface PlanData {
  id: string
  code: string
  name: string
  description: string | null
  is_active: boolean
  is_public: boolean
  sort_order: number
  price_inr_monthly: string
  price_inr_yearly: string
  price_usd_monthly: string
  price_usd_yearly: string
  trial_days: number
  limits: Record<string, number>
  features: string[]
  metadata: Record<string, unknown> | null
}

export interface InvoiceData {
  id: string
  subscription_id: string | null
  invoice_number: string
  status: InvoiceStatus
  currency: Currency
  subtotal: string
  tax: string
  total: string
  amount_paid: string
  amount_due: string
  due_date: string | null
  paid_at: string | null
  razorpay_invoice_id: string | null
  pdf_url: string | null
  line_items: Record<string, unknown>[]
  created_at: string
}

export interface PaymentMethodData {
  id: string
  type: PaymentMethodType
  type_label: string
  is_default: boolean
  card_last_four: string | null
  card_brand: string | null
  card_exp_month: string | null
  card_exp_year: string | null
  bank_name: string | null
  upi_id: string | null
  display_name: string
  is_expired: boolean
  created_at: string
}

export interface BillingSummaryData {
  current_subscription: SubscriptionData | null
  next_billing_date: string | null
  next_billing_amount: string | null
  total_invoices: number
  total_paid: string
  default_payment_method: PaymentMethodData | null
}

export interface UsageData {
  workspaces_used: number
  workspaces_limit: number | null
  social_accounts_used: number
  social_accounts_limit: number | null
  team_members_used: number
  team_members_limit: number | null
  posts_this_month: number
  posts_limit: number | null
}

export interface CreateSubscriptionRequest {
  plan_id: string
  billing_cycle: BillingCycle
  payment_method_id?: string
}

export interface ChangePlanRequest {
  plan_id: string
  billing_cycle?: BillingCycle
}

export interface AddPaymentMethodRequest {
  type: PaymentMethodType
  token: string
}
