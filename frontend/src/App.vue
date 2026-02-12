<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const route = useRoute()

const AppLayout = defineAsyncComponent(() => import('@/layouts/AppLayout.vue'))
const AdminLayout = defineAsyncComponent(() => import('@/layouts/AdminLayout.vue'))

const layoutComponent = computed(() => {
  const l = route.meta.layout
  if (l === 'app') return AppLayout
  if (l === 'admin') return AdminLayout
  return null
})
</script>

<template>
  <Toast position="top-right" />
  <ConfirmDialog />
  <RouterView v-slot="{ Component }">
    <component :is="layoutComponent" v-if="layoutComponent">
      <component :is="Component" />
    </component>
    <component :is="Component" v-else />
  </RouterView>
</template>
