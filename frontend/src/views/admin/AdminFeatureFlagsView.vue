<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminFeatureFlagTable from '@/components/admin/AdminFeatureFlagTable.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminFeatureFlagsApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { FeatureFlagData } from '@/types/admin'

const toast = useToast()
const { confirmDelete } = useConfirm()
const flags = ref<FeatureFlagData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    flags.value = await adminFeatureFlagsApi.list()
  } finally {
    loading.value = false
  }
})

async function toggleFlag(flag: FeatureFlagData) {
  try {
    const updated = await adminFeatureFlagsApi.toggle(flag.id)
    const idx = flags.value.findIndex((f) => f.id === flag.id)
    if (idx >= 0) flags.value[idx] = updated
    toast.success(`Flag ${updated.is_enabled ? 'enabled' : 'disabled'}`)
  } catch { toast.error('Failed to toggle flag') }
}

function editFlag(flag: FeatureFlagData) {
  toast.info(`Edit flag: ${flag.name} (dialog would open here)`)
}

function removeFlag(flag: FeatureFlagData) {
  confirmDelete({
    message: `Delete feature flag "${flag.name}"?`,
    async onAccept() {
      try {
        await adminFeatureFlagsApi.remove(flag.id)
        flags.value = flags.value.filter((f) => f.id !== flag.id)
        toast.success('Flag deleted')
      } catch { toast.error('Failed to delete flag') }
    },
  })
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Feature Flags</h1>

    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <AdminFeatureFlagTable v-else :flags="flags" @toggle="toggleFlag" @edit="editFlag" @remove="removeFlag" />
  </div>
</template>
