<script setup lang="ts">
import { reportsApi } from '@/api/reports'
import type { ReportData } from '@/types/report'
import Button from 'primevue/button'

const props = defineProps<{
  workspaceId: string
  report: ReportData
}>()

function download() {
  const url = reportsApi.downloadUrl(props.workspaceId, props.report.id)
  window.open(url, '_blank')
}
</script>

<template>
  <Button
    v-if="report.is_available"
    icon="pi pi-download"
    severity="info"
    text
    rounded
    size="small"
    v-tooltip="'Download'"
    @click="download"
  />
</template>
