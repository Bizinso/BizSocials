<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import NotificationPreferences from '@/components/notification/NotificationPreferences.vue'
</script>

<template>
  <AppPageHeader title="Notification Settings" description="Control which notifications you receive and how" />

  <AppCard>
    <NotificationPreferences />
  </AppCard>
</template>
