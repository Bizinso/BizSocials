<script setup lang="ts">
import { computed } from 'vue'
import Tag from 'primevue/tag'

const props = defineProps<{
  status: string
  size?: 'small' | 'large'
}>()

const severityMap: Record<string, string> = {
  // Post statuses
  draft: 'secondary',
  submitted: 'info',
  approved: 'success',
  rejected: 'danger',
  scheduled: 'warn',
  publishing: 'info',
  published: 'success',
  failed: 'danger',
  cancelled: 'secondary',
  // Account statuses
  connected: 'success',
  token_expired: 'warn',
  revoked: 'danger',
  disconnected: 'secondary',
  // Generic
  active: 'success',
  inactive: 'secondary',
  pending: 'warn',
  suspended: 'danger',
}

const severity = computed(() =>
  (severityMap[props.status] || 'secondary') as 'success' | 'info' | 'warn' | 'danger' | 'secondary',
)

const label = computed(() =>
  props.status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
)
</script>

<template>
  <Tag :value="label" :severity="severity" />
</template>
