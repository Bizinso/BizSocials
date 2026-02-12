<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import ReportList from '@/components/analytics/ReportList.vue'
import ReportCreateDialog from '@/components/analytics/ReportCreateDialog.vue'
import Button from 'primevue/button'

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)
const showCreateDialog = ref(false)
const reportListKey = ref(0)

function onReportCreated() {
  reportListKey.value++
}
</script>

<template>
  <AppPageHeader title="Reports" description="Generate and download analytics reports">
    <template #actions>
      <Button label="Generate Report" icon="pi pi-file-pdf" @click="showCreateDialog = true" />
    </template>
  </AppPageHeader>

  <AppCard>
    <ReportList :key="reportListKey" :workspace-id="workspaceId" />
  </AppCard>

  <ReportCreateDialog
    v-model:visible="showCreateDialog"
    :workspace-id="workspaceId"
    @created="onReportCreated"
  />
</template>
