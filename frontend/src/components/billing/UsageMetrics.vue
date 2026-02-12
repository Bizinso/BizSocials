<script setup lang="ts">
import { computed } from 'vue'
import type { UsageData } from '@/types/billing'
import ProgressBar from 'primevue/progressbar'

const props = defineProps<{
  usage: UsageData | null
}>()

interface UsageItem {
  label: string
  used: number
  limit: number | null
}

const items = computed<UsageItem[]>(() => {
  if (!props.usage) return []
  return [
    { label: 'Workspaces', used: props.usage.workspaces_used, limit: props.usage.workspaces_limit },
    { label: 'Social Accounts', used: props.usage.social_accounts_used, limit: props.usage.social_accounts_limit },
    { label: 'Team Members', used: props.usage.team_members_used, limit: props.usage.team_members_limit },
    { label: 'Posts This Month', used: props.usage.posts_this_month, limit: props.usage.posts_limit },
  ]
})

function percentage(used: number, limit: number | null): number {
  if (!limit) return 0
  return Math.min(Math.round((used / limit) * 100), 100)
}

function usageColor(used: number, limit: number | null): string {
  if (!limit) return ''
  const pct = (used / limit) * 100
  if (pct >= 90) return 'danger'
  if (pct >= 75) return 'warning'
  return ''
}
</script>

<template>
  <div v-if="usage" class="space-y-4">
    <div v-for="item in items" :key="item.label">
      <div class="mb-1 flex items-center justify-between text-sm">
        <span class="text-gray-700">{{ item.label }}</span>
        <span class="font-medium text-gray-900">
          {{ item.used }}{{ item.limit ? ` / ${item.limit}` : '' }}
          <span v-if="!item.limit" class="text-xs text-gray-400">(Unlimited)</span>
        </span>
      </div>
      <ProgressBar
        v-if="item.limit"
        :value="percentage(item.used, item.limit)"
        :show-value="false"
        style="height: 8px"
      />
    </div>
  </div>
</template>
