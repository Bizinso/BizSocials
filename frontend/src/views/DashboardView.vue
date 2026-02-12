<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { workspaceApi } from '@/api/workspace'
import type { WorkspaceData } from '@/types/workspace'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Button from 'primevue/button'

const router = useRouter()
const workspaces = ref<WorkspaceData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const result = await workspaceApi.list()
    workspaces.value = result.data
  } finally {
    loading.value = false
  }
})

function goToWorkspace(id: string) {
  router.push(`/app/w/${id}`)
}

function createWorkspace() {
  router.push('/app/workspaces')
}
</script>

<template>
  <AppPageHeader title="Dashboard" description="Welcome to BizSocials" />

  <AppLoadingSkeleton v-if="loading" :lines="6" />

  <template v-else-if="workspaces.length > 0">
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <AppCard>
        <div class="text-center">
          <i class="pi pi-briefcase mb-2 text-2xl text-primary-500" />
          <p class="text-2xl font-bold text-gray-900">{{ workspaces.length }}</p>
          <p class="text-sm text-gray-500">Workspaces</p>
        </div>
      </AppCard>
    </div>

    <h2 class="mb-4 text-lg font-semibold text-gray-900">Your Workspaces</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <AppCard
        v-for="ws in workspaces"
        :key="ws.id"
        class="cursor-pointer transition-shadow hover:shadow-md"
        @click="goToWorkspace(ws.id)"
      >
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-700 font-bold">
            {{ ws.name.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-gray-900">{{ ws.name }}</p>
            <p class="text-sm text-gray-500">{{ ws.member_count || 0 }} members</p>
          </div>
          <i class="pi pi-chevron-right text-gray-400" />
        </div>
      </AppCard>
    </div>
  </template>

  <AppEmptyState
    v-else
    icon="pi pi-briefcase"
    title="No workspaces yet"
    description="Create your first workspace to start managing social media."
  >
    <template #actions>
      <Button label="Create Workspace" icon="pi pi-plus" @click="createWorkspace" />
    </template>
  </AppEmptyState>
</template>
