<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PrivacySettings from '@/components/settings/PrivacySettings.vue'
</script>

<template>
  <AppPageHeader title="Data Privacy" description="Manage your data export and deletion requests" />

  <AppCard>
    <PrivacySettings />
  </AppCard>
</template>
