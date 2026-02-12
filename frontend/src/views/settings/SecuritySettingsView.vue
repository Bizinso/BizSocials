<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import SecuritySettings from '@/components/settings/SecuritySettings.vue'
</script>

<template>
  <AppPageHeader title="Security" description="Monitor security events and activity" />

  <AppCard>
    <SecuritySettings />
  </AppCard>
</template>
