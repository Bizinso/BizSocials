import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '@/stores/workspace'

export function useWorkspace() {
  const workspaceStore = useWorkspaceStore()
  const router = useRouter()

  const workspaces = computed(() => workspaceStore.workspaces)
  const currentWorkspace = computed(() => workspaceStore.currentWorkspace)
  const currentWorkspaceId = computed(() => workspaceStore.currentWorkspaceId)
  const loading = computed(() => workspaceStore.loading)

  function switchWorkspace(id: string) {
    workspaceStore.setCurrentWorkspace(id)
    // Navigate to workspace dashboard
    router.push(`/app/w/${id}/posts`)
  }

  return {
    workspaces,
    currentWorkspace,
    currentWorkspaceId,
    loading,
    fetchWorkspaces: workspaceStore.fetchWorkspaces,
    switchWorkspace,
  }
}
