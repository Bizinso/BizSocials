import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { workspaceApi } from '@/api/workspace'
import type { WorkspaceData } from '@/types/workspace'

export const useWorkspaceStore = defineStore(
  'workspace',
  () => {
    const workspaces = ref<WorkspaceData[]>([])
    const currentWorkspaceId = ref<string | null>(null)
    const loading = ref(false)

    const currentWorkspace = computed(() =>
      workspaces.value.find((w) => w.id === currentWorkspaceId.value) || null,
    )

    async function fetchWorkspaces() {
      loading.value = true
      try {
        const response = await workspaceApi.list({ per_page: 100 })
        workspaces.value = response.data
        // Auto-select first workspace if none selected
        if (!currentWorkspaceId.value && workspaces.value.length > 0) {
          currentWorkspaceId.value = workspaces.value[0].id
        }
      } finally {
        loading.value = false
      }
    }

    function setCurrentWorkspace(id: string) {
      currentWorkspaceId.value = id
    }

    function addWorkspace(workspace: WorkspaceData) {
      workspaces.value.push(workspace)
    }

    function updateWorkspace(workspace: WorkspaceData) {
      const index = workspaces.value.findIndex((w) => w.id === workspace.id)
      if (index !== -1) {
        workspaces.value[index] = workspace
      }
    }

    function removeWorkspace(id: string) {
      workspaces.value = workspaces.value.filter((w) => w.id !== id)
      if (currentWorkspaceId.value === id) {
        currentWorkspaceId.value = workspaces.value[0]?.id || null
      }
    }

    function clear() {
      workspaces.value = []
      currentWorkspaceId.value = null
    }

    return {
      workspaces,
      currentWorkspaceId,
      currentWorkspace,
      loading,
      fetchWorkspaces,
      setCurrentWorkspace,
      addWorkspace,
      updateWorkspace,
      removeWorkspace,
      clear,
    }
  },
  {
    persist: {
      paths: ['currentWorkspaceId'],
    },
  },
)
