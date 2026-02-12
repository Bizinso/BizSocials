<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminConfigEditor from '@/components/admin/AdminConfigEditor.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminConfigApi } from '@/api/admin'
import type { PlatformConfigData } from '@/types/admin'

const configs = ref<Record<string, PlatformConfigData[]>>({})
const loading = ref(true)

onMounted(async () => {
  try {
    configs.value = await adminConfigApi.getGrouped()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Platform Configuration</h1>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <AdminConfigEditor v-else :configs="configs" />
  </div>
</template>
