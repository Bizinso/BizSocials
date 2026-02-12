import type { ReportType, ReportStatus } from './enums'

export interface ReportData {
  id: string
  workspace_id: string
  created_by_user_id: string
  created_by_user_name: string | null
  name: string
  description: string | null
  report_type: ReportType
  report_type_label: string
  date_from: string
  date_to: string
  social_account_ids: string[] | null
  metrics: Record<string, unknown> | null
  filters: Record<string, unknown> | null
  status: ReportStatus
  status_label: string
  file_path: string | null
  file_format: string
  file_size_bytes: number | null
  file_size_human: string | null
  is_available: boolean
  is_expired: boolean
  completed_at: string | null
  expires_at: string | null
  created_at: string
  updated_at: string
}

export interface CreateReportRequest {
  name: string
  description?: string | null
  report_type: ReportType
  date_from: string
  date_to: string
  social_account_ids?: string[] | null
  metrics?: string[] | null
  file_format?: string
}
