import { defineStore } from 'pinia'
import { ref } from 'vue'
import { teamApi } from '@/api/team'
import type { TeamData, TeamDetailData, CreateTeamRequest, UpdateTeamRequest } from '@/types/team'

export const useTeamStore = defineStore('team', () => {
  const teams = ref<TeamData[]>([])
  const currentTeam = ref<TeamDetailData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string[]>>({})

  async function fetchTeams(workspaceId: string) {
    loading.value = true
    error.value = null
    try {
      const response = await teamApi.list(workspaceId, { per_page: 100 })
      teams.value = response.data
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message ?? 'Failed to load teams'
    } finally {
      loading.value = false
    }
  }

  async function fetchTeam(workspaceId: string, teamId: string) {
    loading.value = true
    error.value = null
    try {
      currentTeam.value = await teamApi.get(workspaceId, teamId)
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message ?? 'Failed to load team'
    } finally {
      loading.value = false
    }
  }

  async function createTeam(workspaceId: string, data: CreateTeamRequest) {
    loading.value = true
    error.value = null
    fieldErrors.value = {}
    try {
      const team = await teamApi.create(workspaceId, data)
      teams.value.push(team)
      return team
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosError.response?.data?.errors) {
        fieldErrors.value = axiosError.response.data.errors
      }
      error.value = axiosError.response?.data?.message ?? 'Failed to create team'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTeam(workspaceId: string, teamId: string, data: UpdateTeamRequest) {
    loading.value = true
    error.value = null
    fieldErrors.value = {}
    try {
      const updated = await teamApi.update(workspaceId, teamId, data)
      const index = teams.value.findIndex((t) => t.id === teamId)
      if (index !== -1) {
        teams.value[index] = updated
      }
      return updated
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosError.response?.data?.errors) {
        fieldErrors.value = axiosError.response.data.errors
      }
      error.value = axiosError.response?.data?.message ?? 'Failed to update team'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTeam(workspaceId: string, teamId: string) {
    loading.value = true
    error.value = null
    try {
      await teamApi.delete(workspaceId, teamId)
      teams.value = teams.value.filter((t) => t.id !== teamId)
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message ?? 'Failed to delete team'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function addMember(workspaceId: string, teamId: string, userId: string) {
    error.value = null
    try {
      const member = await teamApi.addMember(workspaceId, teamId, { user_id: userId })
      // Refresh team detail if we're viewing it
      if (currentTeam.value?.id === teamId) {
        currentTeam.value.members.push(member)
        currentTeam.value.member_count++
      }
      // Update count in list
      const team = teams.value.find((t) => t.id === teamId)
      if (team) team.member_count++
      return member
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message ?? 'Failed to add member'
      throw err
    }
  }

  async function removeMember(workspaceId: string, teamId: string, userId: string) {
    error.value = null
    try {
      await teamApi.removeMember(workspaceId, teamId, userId)
      if (currentTeam.value?.id === teamId) {
        currentTeam.value.members = currentTeam.value.members.filter((m) => m.user_id !== userId)
        currentTeam.value.member_count--
      }
      const team = teams.value.find((t) => t.id === teamId)
      if (team) team.member_count--
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message ?? 'Failed to remove member'
      throw err
    }
  }

  function clear() {
    teams.value = []
    currentTeam.value = null
    error.value = null
    fieldErrors.value = {}
  }

  return {
    teams,
    currentTeam,
    loading,
    error,
    fieldErrors,
    fetchTeams,
    fetchTeam,
    createTeam,
    updateTeam,
    deleteTeam,
    addMember,
    removeMember,
    clear,
  }
})
