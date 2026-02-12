export interface WebhookEndpointData {
  id: string
  workspace_id: string
  url: string
  secret: string
  events: string[]
  is_active: boolean
  failure_count: number
  last_triggered_at: string | null
  created_at: string
}

export interface WebhookDeliveryData {
  id: string
  webhook_endpoint_id: string
  event: string
  payload: Record<string, unknown>
  response_code: number | null
  response_body: string | null
  duration_ms: number | null
  delivered_at: string | null
  created_at: string
}

export interface CreateWebhookEndpointRequest {
  url: string
  events: string[]
  is_active?: boolean
}
