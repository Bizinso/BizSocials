import { get, post, put, del, getPaginated } from './client'
import type { AudienceDemographicData, HashtagPerformanceData, ScheduledReportData } from '@/types/analytics-extended'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

export const audienceDemographicsApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<AudienceDemographicData>(`${base(workspaceId)}/analytics/demographics`, params as Record<string, unknown>)
  },
  latest(workspaceId: string) {
    return get<AudienceDemographicData>(`${base(workspaceId)}/analytics/demographics/latest`)
  },
  fetch(workspaceId: string) {
    return post<void>(`${base(workspaceId)}/analytics/demographics/fetch`, {})
  },
}

export const hashtagTrackingApi = {
  list(workspaceId: string, params?: PaginationParams & { search?: string; sort?: string }) {
    return getPaginated<HashtagPerformanceData>(`${base(workspaceId)}/hashtag-tracking`, params as Record<string, unknown>)
  },
  get(workspaceId: string, hashtag: string) {
    return get<HashtagPerformanceData>(`${base(workspaceId)}/hashtag-tracking/${hashtag}`)
  },
}

export const scheduledReportApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<ScheduledReportData>(`${base(workspaceId)}/scheduled-reports`, params as Record<string, unknown>)
  },
  show(workspaceId: string, id: string) {
    return get<ScheduledReportData>(`${base(workspaceId)}/scheduled-reports/${id}`)
  },
  create(workspaceId: string, data: Record<string, unknown>) {
    return post<ScheduledReportData>(`${base(workspaceId)}/scheduled-reports`, data)
  },
  update(workspaceId: string, id: string, data: Record<string, unknown>) {
    return put<ScheduledReportData>(`${base(workspaceId)}/scheduled-reports/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/scheduled-reports/${id}`)
  },
}
