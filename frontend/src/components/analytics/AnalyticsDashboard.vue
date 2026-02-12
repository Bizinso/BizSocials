<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { AnalyticsParams } from '@/api/analytics'
import MetricCard from './MetricCard.vue'
import EngagementChart from './EngagementChart.vue'
import PlatformBreakdown from './PlatformBreakdown.vue'
import TopPostsList from './TopPostsList.vue'
import BestTimesHeatmap from './BestTimesHeatmap.vue'
import ContentTypeChart from './ContentTypeChart.vue'
import AppDateRangePicker from '@/components/shared/AppDateRangePicker.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'

const props = defineProps<{
  workspaceId: string
}>()

const analyticsStore = useAnalyticsStore()
const toast = useToast()
const period = ref<AnalyticsParams>({})

onMounted(() => fetchAll())

async function fetchAll() {
  const params = { ...period.value }
  try {
    await Promise.all([
      analyticsStore.fetchDashboard(props.workspaceId, params),
      analyticsStore.fetchTrends(props.workspaceId, { ...params, metric: 'engagements' }),
      analyticsStore.fetchPlatforms(props.workspaceId, params),
      analyticsStore.fetchTopPosts(props.workspaceId, { ...params, limit: 5 }),
      analyticsStore.fetchBestTimes(props.workspaceId, params),
      analyticsStore.fetchContentPerformance(props.workspaceId, params),
    ])
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function onDateChange(range: { start_date: string; end_date: string }) {
  period.value = { start_date: range.start_date, end_date: range.end_date }
  fetchAll()
}
</script>

<template>
  <div class="space-y-6">
    <AppDateRangePicker @change="onDateChange" />

    <AppLoadingSkeleton v-if="analyticsStore.loading" :lines="4" :count="2" />

    <template v-else-if="analyticsStore.dashboard">
      <!-- Metric cards -->
      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <MetricCard label="Impressions" :value="analyticsStore.dashboard.impressions" :change="analyticsStore.dashboard.impressions_change" icon="pi pi-eye" />
        <MetricCard label="Reach" :value="analyticsStore.dashboard.reach" :change="analyticsStore.dashboard.reach_change" icon="pi pi-users" />
        <MetricCard label="Engagements" :value="analyticsStore.dashboard.engagements" :change="analyticsStore.dashboard.engagement_change" icon="pi pi-heart" />
        <MetricCard label="Engagement Rate" :value="analyticsStore.dashboard.engagement_rate" :change="null" icon="pi pi-percentage" format="rate" />
      </div>

      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <MetricCard label="Followers" :value="analyticsStore.dashboard.followers_total" :change="analyticsStore.dashboard.followers_change" icon="pi pi-user-plus" />
        <MetricCard label="Posts Published" :value="analyticsStore.dashboard.posts_published" icon="pi pi-file-edit" />
        <MetricCard label="Likes" :value="analyticsStore.dashboard.likes" icon="pi pi-thumbs-up" />
        <MetricCard label="Comments" :value="analyticsStore.dashboard.comments" icon="pi pi-comment" />
      </div>

      <!-- Charts row 1 -->
      <div class="grid gap-6 lg:grid-cols-2">
        <AppCard title="Engagement Trend">
          <EngagementChart :data="analyticsStore.trends" />
        </AppCard>
        <AppCard title="Platform Breakdown">
          <PlatformBreakdown :platforms="analyticsStore.platforms" />
        </AppCard>
      </div>

      <!-- Charts row 2 -->
      <div class="grid gap-6 lg:grid-cols-2">
        <AppCard title="Content Type Performance">
          <ContentTypeChart :data="analyticsStore.contentPerformance" />
        </AppCard>
        <AppCard title="Best Times to Post">
          <BestTimesHeatmap :data="analyticsStore.bestTimes" />
        </AppCard>
      </div>

      <!-- Top posts -->
      <AppCard title="Top Performing Posts">
        <TopPostsList :posts="analyticsStore.topPosts" />
      </AppCard>
    </template>
  </div>
</template>
