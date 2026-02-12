<script setup lang="ts">
import { useRouter } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PlanSelector from '@/components/billing/PlanSelector.vue'

const router = useRouter()

function onSubscribed() {
  router.push('/app/billing/overview')
}
</script>

<template>
  <AppPageHeader title="Plans" description="Choose the plan that fits your needs" />

  <AppCard>
    <PlanSelector @subscribed="onSubscribed" />
  </AppCard>
</template>
