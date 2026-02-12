import type { DataRequestStatus, DataRequestType } from './enums'

export interface DataExportRequestData {
  id: string
  user_id: string
  status: DataRequestStatus
  request_type: DataRequestType
  format: string
  data_categories: string[] | null
  file_path: string | null
  file_size_bytes: number | null
  download_url: string | null
  download_count: number
  expires_at: string | null
  completed_at: string | null
  failure_reason: string | null
  created_at: string
}

export interface DataDeletionRequestData {
  id: string
  user_id: string
  status: DataRequestStatus
  data_categories: string[] | null
  reason: string | null
  requires_approval: boolean
  approved_by: string | null
  approved_at: string | null
  scheduled_for: string | null
  completed_at: string | null
  deletion_summary: Record<string, unknown> | null
  failure_reason: string | null
  created_at: string
}

export interface RequestExportRequest {
  format?: string
  data_categories?: string[]
}

export interface RequestDeletionRequest {
  data_categories?: string[]
  reason?: string
}
