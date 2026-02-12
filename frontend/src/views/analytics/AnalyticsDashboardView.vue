<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard.vue'

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)
</script>

<template>
  <AppPageHeader title="Analytics" description="Track performance across your social channels" />
  <AnalyticsDashboard :workspace-id="workspaceId" />
</template>
