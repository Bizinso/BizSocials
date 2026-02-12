import { get, post, del, getPaginated } from './client'
import { apiClient } from './client'
import type { ReportData, CreateReportRequest } from '@/types/report'
import type { PaginationParams } from '@/types/api'

export const reportsApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<ReportData>(`/workspaces/${workspaceId}/reports`, params as Record<string, unknown>)
  },

  get(workspaceId: string, reportId: string) {
    return get<ReportData>(`/workspaces/${workspaceId}/reports/${reportId}`)
  },

  create(workspaceId: string, data: CreateReportRequest) {
    return post<ReportData>(`/workspaces/${workspaceId}/reports`, data)
  },

  delete(workspaceId: string, reportId: string) {
    return del(`/workspaces/${workspaceId}/reports/${reportId}`)
  },

  downloadUrl(workspaceId: string, reportId: string): string {
    return `${apiClient.defaults.baseURL}/workspaces/${workspaceId}/reports/${reportId}/download`
  },
}
