import { get } from './client'
import type {
  DashboardMetricsData,
  TrendDataPoint,
  PlatformMetricsData,
  ContentPerformanceData,
  TopPostData,
  BestTimesData,
} from '@/types/analytics'

export interface AnalyticsParams {
  period?: string
  start_date?: string
  end_date?: string
  platform?: string
}

export const analyticsApi = {
  dashboard(workspaceId: string, params?: AnalyticsParams) {
    return get<DashboardMetricsData>(`/workspaces/${workspaceId}/analytics/dashboard`, params as Record<string, unknown>)
  },

  metrics(workspaceId: string, params?: AnalyticsParams & { metric?: string }) {
    return get<TrendDataPoint[]>(`/workspaces/${workspaceId}/analytics/metrics`, params as Record<string, unknown>)
  },

  trends(workspaceId: string, params?: AnalyticsParams & { metric?: string }) {
    return get<TrendDataPoint[]>(`/workspaces/${workspaceId}/analytics/trends`, params as Record<string, unknown>)
  },

  platforms(workspaceId: string, params?: AnalyticsParams) {
    return get<PlatformMetricsData[]>(`/workspaces/${workspaceId}/analytics/platforms`, params as Record<string, unknown>)
  },

  contentOverview(workspaceId: string, params?: AnalyticsParams) {
    return get<ContentPerformanceData[]>(`/workspaces/${workspaceId}/analytics/content/overview`, params as Record<string, unknown>)
  },

  topPosts(workspaceId: string, params?: AnalyticsParams & { limit?: number }) {
    return get<TopPostData[]>(`/workspaces/${workspaceId}/analytics/content/top-posts`, params as Record<string, unknown>)
  },

  byContentType(workspaceId: string, params?: AnalyticsParams) {
    return get<ContentPerformanceData[]>(`/workspaces/${workspaceId}/analytics/content/by-type`, params as Record<string, unknown>)
  },

  bestTimes(workspaceId: string, params?: AnalyticsParams) {
    return get<BestTimesData[]>(`/workspaces/${workspaceId}/analytics/content/best-times`, params as Record<string, unknown>)
  },
}
