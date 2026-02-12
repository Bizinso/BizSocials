<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Breadcrumb from 'primevue/breadcrumb'

const route = useRoute()

const items = computed(() => {
  const segments = route.path.split('/').filter(Boolean)
  const breadcrumbs: { label: string; to?: string }[] = []

  let path = ''
  for (const segment of segments) {
    path += `/${segment}`
    // Skip 'app' and 'w' segments, and UUID-like segments
    if (segment === 'app' || segment === 'w' || /^[0-9a-f-]{36}$/.test(segment)) continue
    breadcrumbs.push({
      label: segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' '),
      to: path,
    })
  }

  return breadcrumbs
})

const home = { icon: 'pi pi-home', to: '/app/dashboard' }
</script>

<template>
  <Breadcrumb :home="home" :model="items" class="border-0 bg-transparent p-0" />
</template>
