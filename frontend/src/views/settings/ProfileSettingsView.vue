<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import ProfileForm from '@/components/settings/ProfileForm.vue'
</script>

<template>
  <AppPageHeader title="Profile Settings" description="Manage your personal information" />
  <AppCard>
    <ProfileForm />
  </AppCard>
</template>
