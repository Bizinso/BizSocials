import { get, post, put, del, getPaginated } from './client'
import type { WebhookEndpointData, WebhookDeliveryData } from '@/types/webhooks'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

export const webhookApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<WebhookEndpointData>(`${base(workspaceId)}/webhook-endpoints`, params as Record<string, unknown>)
  },
  get(workspaceId: string, id: string) {
    return get<WebhookEndpointData>(`${base(workspaceId)}/webhook-endpoints/${id}`)
  },
  create(workspaceId: string, data: Record<string, unknown>) {
    return post<WebhookEndpointData>(`${base(workspaceId)}/webhook-endpoints`, data)
  },
  update(workspaceId: string, id: string, data: Record<string, unknown>) {
    return put<WebhookEndpointData>(`${base(workspaceId)}/webhook-endpoints/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/webhook-endpoints/${id}`)
  },
  deliveries(workspaceId: string, endpointId: string, params?: PaginationParams) {
    return getPaginated<WebhookDeliveryData>(`${base(workspaceId)}/webhook-endpoints/${endpointId}/deliveries`, params as Record<string, unknown>)
  },
  test(workspaceId: string, endpointId: string) {
    return post<void>(`${base(workspaceId)}/webhook-endpoints/${endpointId}/test`, {})
  },
}
