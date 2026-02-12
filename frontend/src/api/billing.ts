import { get, post, put, del, getPaginated } from './client'
import { apiClient } from './client'
import type {
  SubscriptionData,
  PlanData,
  InvoiceData,
  PaymentMethodData,
  BillingSummaryData,
  UsageData,
  CreateSubscriptionRequest,
  ChangePlanRequest,
  AddPaymentMethodRequest,
} from '@/types/billing'
import type { PaginationParams } from '@/types/api'

export const billingApi = {
  // Summary & Usage
  summary() {
    return get<BillingSummaryData>('/billing/summary')
  },

  usage() {
    return get<UsageData>('/billing/usage')
  },

  plans() {
    return get<PlanData[]>('/billing/plans')
  },

  // Subscription
  getSubscription() {
    return get<SubscriptionData>('/billing/subscription')
  },

  createSubscription(data: CreateSubscriptionRequest) {
    return post<SubscriptionData>('/billing/subscription', data)
  },

  changePlan(data: ChangePlanRequest) {
    return put<SubscriptionData>('/billing/subscription/plan', data)
  },

  cancelSubscription() {
    return post<SubscriptionData>('/billing/subscription/cancel')
  },

  reactivateSubscription() {
    return post<SubscriptionData>('/billing/subscription/reactivate')
  },

  // Invoices
  listInvoices(params?: PaginationParams) {
    return getPaginated<InvoiceData>('/billing/invoices', params as Record<string, unknown>)
  },

  getInvoice(invoiceId: string) {
    return get<InvoiceData>(`/billing/invoices/${invoiceId}`)
  },

  downloadInvoiceUrl(invoiceId: string): string {
    return `${apiClient.defaults.baseURL}/billing/invoices/${invoiceId}/download`
  },

  // Payment Methods
  listPaymentMethods() {
    return get<PaymentMethodData[]>('/billing/payment-methods')
  },

  addPaymentMethod(data: AddPaymentMethodRequest) {
    return post<PaymentMethodData>('/billing/payment-methods', data)
  },

  setDefaultPaymentMethod(paymentMethodId: string) {
    return put<PaymentMethodData>(`/billing/payment-methods/${paymentMethodId}/default`)
  },

  removePaymentMethod(paymentMethodId: string) {
    return del(`/billing/payment-methods/${paymentMethodId}`)
  },
}
