<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspace } from '@/composables/useWorkspace'
import type { WorkspaceData } from '@/types/workspace'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import WorkspaceCard from '@/components/workspace/WorkspaceCard.vue'
import WorkspaceCreateDialog from '@/components/workspace/WorkspaceCreateDialog.vue'
import Button from 'primevue/button'

const router = useRouter()
const { workspaces, loading, fetchWorkspaces, switchWorkspace } = useWorkspace()

const showCreateDialog = ref(false)

onMounted(() => fetchWorkspaces())

function onSelectWorkspace(workspace: WorkspaceData) {
  switchWorkspace(workspace.id)
}
</script>

<template>
  <AppPageHeader title="Workspaces" description="Manage your team workspaces">
    <template #actions>
      <Button label="New workspace" icon="pi pi-plus" @click="showCreateDialog = true" />
    </template>
  </AppPageHeader>

  <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div v-for="i in 3" :key="i" class="rounded-lg border border-gray-200 bg-white p-5">
      <AppLoadingSkeleton :lines="3" />
    </div>
  </div>

  <AppEmptyState
    v-else-if="workspaces.length === 0"
    icon="pi pi-th-large"
    title="No workspaces yet"
    description="Create your first workspace to start managing social media."
  >
    <Button label="Create workspace" icon="pi pi-plus" @click="showCreateDialog = true" />
  </AppEmptyState>

  <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <WorkspaceCard
      v-for="workspace in workspaces"
      :key="workspace.id"
      :workspace="workspace"
      @select="onSelectWorkspace"
    />
  </div>

  <WorkspaceCreateDialog v-model:visible="showCreateDialog" @created="fetchWorkspaces" />
</template>
