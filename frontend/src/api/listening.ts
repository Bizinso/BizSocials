import { get, post, put, del, getPaginated } from './client'
import type { MonitoredKeywordData, KeywordMentionData } from '@/types/listening'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

export const keywordMonitoringApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<MonitoredKeywordData>(`${base(workspaceId)}/monitored-keywords`, params as Record<string, unknown>)
  },
  get(workspaceId: string, id: string) {
    return get<MonitoredKeywordData>(`${base(workspaceId)}/monitored-keywords/${id}`)
  },
  create(workspaceId: string, data: Record<string, unknown>) {
    return post<MonitoredKeywordData>(`${base(workspaceId)}/monitored-keywords`, data)
  },
  update(workspaceId: string, id: string, data: Record<string, unknown>) {
    return put<MonitoredKeywordData>(`${base(workspaceId)}/monitored-keywords/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/monitored-keywords/${id}`)
  },
  mentions(workspaceId: string, keywordId: string, params?: PaginationParams) {
    return getPaginated<KeywordMentionData>(`${base(workspaceId)}/monitored-keywords/${keywordId}/mentions`, params as Record<string, unknown>)
  },
}
