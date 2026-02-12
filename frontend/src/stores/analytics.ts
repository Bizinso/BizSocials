import { defineStore } from 'pinia'
import { ref } from 'vue'
import { analyticsApi, type AnalyticsParams } from '@/api/analytics'
import type {
  DashboardMetricsData,
  TrendDataPoint,
  PlatformMetricsData,
  ContentPerformanceData,
  TopPostData,
  BestTimesData,
} from '@/types/analytics'

export const useAnalyticsStore = defineStore('analytics', () => {
  const dashboard = ref<DashboardMetricsData | null>(null)
  const trends = ref<TrendDataPoint[]>([])
  const platforms = ref<PlatformMetricsData[]>([])
  const contentPerformance = ref<ContentPerformanceData[]>([])
  const topPosts = ref<TopPostData[]>([])
  const bestTimes = ref<BestTimesData[]>([])
  const loading = ref(false)

  async function fetchDashboard(workspaceId: string, params?: AnalyticsParams) {
    loading.value = true
    try {
      dashboard.value = await analyticsApi.dashboard(workspaceId, params)
    } finally {
      loading.value = false
    }
  }

  async function fetchTrends(workspaceId: string, params?: AnalyticsParams & { metric?: string }) {
    trends.value = await analyticsApi.trends(workspaceId, params)
  }

  async function fetchPlatforms(workspaceId: string, params?: AnalyticsParams) {
    platforms.value = await analyticsApi.platforms(workspaceId, params)
  }

  async function fetchContentPerformance(workspaceId: string, params?: AnalyticsParams) {
    contentPerformance.value = await analyticsApi.contentOverview(workspaceId, params)
  }

  async function fetchTopPosts(workspaceId: string, params?: AnalyticsParams & { limit?: number }) {
    topPosts.value = await analyticsApi.topPosts(workspaceId, params)
  }

  async function fetchBestTimes(workspaceId: string, params?: AnalyticsParams) {
    bestTimes.value = await analyticsApi.bestTimes(workspaceId, params)
  }

  function clear() {
    dashboard.value = null
    trends.value = []
    platforms.value = []
    contentPerformance.value = []
    topPosts.value = []
    bestTimes.value = []
  }

  return {
    dashboard,
    trends,
    platforms,
    contentPerformance,
    topPosts,
    bestTimes,
    loading,
    fetchDashboard,
    fetchTrends,
    fetchPlatforms,
    fetchContentPerformance,
    fetchTopPosts,
    fetchBestTimes,
    clear,
  }
})
