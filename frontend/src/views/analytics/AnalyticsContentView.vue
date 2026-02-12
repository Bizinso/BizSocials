<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAnalyticsStore } from '@/stores/analytics'
import type { AnalyticsParams } from '@/api/analytics'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppDateRangePicker from '@/components/shared/AppDateRangePicker.vue'
import ContentTypeChart from '@/components/analytics/ContentTypeChart.vue'
import TopPostsList from '@/components/analytics/TopPostsList.vue'
import BestTimesHeatmap from '@/components/analytics/BestTimesHeatmap.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'

const route = useRoute()
const analyticsStore = useAnalyticsStore()
const workspaceId = computed(() => route.params.workspaceId as string)
const params = ref<AnalyticsParams>({})

onMounted(() => fetchAll())

async function fetchAll() {
  await Promise.all([
    analyticsStore.fetchContentPerformance(workspaceId.value, params.value),
    analyticsStore.fetchTopPosts(workspaceId.value, { ...params.value, limit: 10 }),
    analyticsStore.fetchBestTimes(workspaceId.value, params.value),
  ])
}

function onDateChange(range: { start_date: string; end_date: string }) {
  params.value = { start_date: range.start_date, end_date: range.end_date }
  fetchAll()
}
</script>

<template>
  <AppPageHeader title="Content Analytics" description="Analyze content performance by type and timing" />

  <div class="space-y-6">
    <AppDateRangePicker @change="onDateChange" />

    <div class="grid gap-6 lg:grid-cols-2">
      <AppCard title="Performance by Content Type">
        <ContentTypeChart :data="analyticsStore.contentPerformance" />
      </AppCard>
      <AppCard title="Best Times to Post">
        <BestTimesHeatmap :data="analyticsStore.bestTimes" />
      </AppCard>
    </div>

    <AppCard title="Top Performing Posts">
      <TopPostsList :posts="analyticsStore.topPosts" />
    </AppCard>
  </div>
</template>
