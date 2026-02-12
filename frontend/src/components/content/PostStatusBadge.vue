<script setup lang="ts">
import AppStatusBadge from '@/components/shared/AppStatusBadge.vue'

defineProps<{
  status: string
}>()
</script>

<template>
  <AppStatusBadge :status="status" />
</template>
