<script setup lang="ts">
import { onMounted } from 'vue'
import { useTenantStore } from '@/stores/tenant'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import TenantSettings from '@/components/tenant/TenantSettings.vue'

const tenantStore = useTenantStore()

onMounted(() => {
  if (!tenantStore.tenant) {
    tenantStore.fetchTenant()
  }
})
</script>

<template>
  <AppPageHeader title="Organization Settings" description="Manage your organization details" />
  <AppCard>
    <TenantSettings />
  </AppCard>
</template>
