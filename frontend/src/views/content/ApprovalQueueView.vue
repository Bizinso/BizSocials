<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import ApprovalQueue from '@/components/content/ApprovalQueue.vue'

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)
</script>

<template>
  <AppPageHeader title="Approval Queue" description="Review and approve content before publishing" />

  <AppCard>
    <ApprovalQueue :workspace-id="workspaceId" />
  </AppCard>
</template>
