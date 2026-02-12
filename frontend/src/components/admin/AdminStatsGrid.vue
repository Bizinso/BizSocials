<script setup lang="ts">
import type { PlatformStatsData } from '@/types/admin'

defineProps<{
  stats: PlatformStatsData
}>()
</script>

<template>
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-lg border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Total Tenants</p>
      <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_tenants }}</p>
      <p class="text-xs text-green-600">{{ stats.active_tenants }} active</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Total Users</p>
      <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_users }}</p>
      <p class="text-xs text-green-600">{{ stats.active_users }} active</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Workspaces</p>
      <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_workspaces }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Subscriptions</p>
      <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.active_subscriptions }}</p>
      <p class="text-xs text-yellow-600">{{ stats.trial_subscriptions }} trials</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
      <p class="text-sm text-gray-500">Suspended</p>
      <p class="mt-1 text-2xl font-bold text-red-600">{{ stats.suspended_tenants }}</p>
    </div>
  </div>
</template>
