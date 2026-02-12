<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useTeamStore } from '@/stores/team'
import { useWorkspaceStore } from '@/stores/workspace'
import { usePermissions } from '@/composables/usePermissions'
import type { TeamData, TeamDetailData } from '@/types/team'
import type { WorkspaceMemberData } from '@/types/workspace'
import { workspaceApi } from '@/api/workspace'

const route = useRoute()
const teamStore = useTeamStore()
const workspaceStore = useWorkspaceStore()

const { canManageMembers } = usePermissions()
const workspaceId = computed(() => route.params.workspaceId as string)

// Create/edit modal
const showModal = ref(false)
const editingTeam = ref<TeamData | null>(null)
const formName = ref('')
const formDescription = ref('')
const formIsDefault = ref(false)
const localErrors = ref<Record<string, string>>({})

// Team detail / member management
const selectedTeam = ref<TeamDetailData | null>(null)
const showMemberModal = ref(false)
const workspaceMembers = ref<WorkspaceMemberData[]>([])

onMounted(() => {
  teamStore.fetchTeams(workspaceId.value)
})

watch(workspaceId, (id) => {
  if (id) teamStore.fetchTeams(id)
})

function openCreateModal() {
  editingTeam.value = null
  formName.value = ''
  formDescription.value = ''
  formIsDefault.value = false
  localErrors.value = {}
  teamStore.fieldErrors = {}
  teamStore.error = null
  showModal.value = true
}

function openEditModal(team: TeamData) {
  editingTeam.value = team
  formName.value = team.name
  formDescription.value = team.description ?? ''
  formIsDefault.value = team.is_default
  localErrors.value = {}
  teamStore.fieldErrors = {}
  teamStore.error = null
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingTeam.value = null
}

function validateForm(): boolean {
  const errors: Record<string, string> = {}
  if (!formName.value.trim()) errors.name = 'Team name is required'
  else if (formName.value.trim().length < 2) errors.name = 'Name must be at least 2 characters'
  else if (formName.value.trim().length > 100) errors.name = 'Name must be 100 characters or less'
  localErrors.value = errors
  return Object.keys(errors).length === 0
}

function getFieldError(field: string): string | undefined {
  return localErrors.value[field] || teamStore.fieldErrors[field]?.[0]
}

async function handleSubmit() {
  if (!validateForm()) return

  try {
    if (editingTeam.value) {
      await teamStore.updateTeam(workspaceId.value, editingTeam.value.id, {
        name: formName.value.trim(),
        description: formDescription.value.trim() || null,
        is_default: formIsDefault.value,
      })
    } else {
      await teamStore.createTeam(workspaceId.value, {
        name: formName.value.trim(),
        description: formDescription.value.trim() || null,
        is_default: formIsDefault.value,
      })
    }
    closeModal()
  } catch {
    // Handled by store
  }
}

async function handleDelete(team: TeamData) {
  if (!confirm(`Delete team "${team.name}"?`)) return
  try {
    await teamStore.deleteTeam(workspaceId.value, team.id)
    if (selectedTeam.value?.id === team.id) {
      selectedTeam.value = null
    }
  } catch {
    // Handled by store
  }
}

async function viewTeamMembers(team: TeamData) {
  await teamStore.fetchTeam(workspaceId.value, team.id)
  selectedTeam.value = teamStore.currentTeam
}

async function openAddMemberModal() {
  try {
    const response = await workspaceApi.getMembers(workspaceId.value, { per_page: 100 })
    workspaceMembers.value = response.data
  } catch {
    // ignore
  }
  showMemberModal.value = true
}

function closeMemberModal() {
  showMemberModal.value = false
}

const availableMembers = computed(() => {
  if (!selectedTeam.value) return []
  const existingIds = new Set(selectedTeam.value.members.map((m) => m.user_id))
  return workspaceMembers.value.filter((m) => !existingIds.has(m.user_id))
})

async function handleAddMember(userId: string) {
  if (!selectedTeam.value) return
  try {
    await teamStore.addMember(workspaceId.value, selectedTeam.value.id, userId)
    selectedTeam.value = teamStore.currentTeam
    // Refresh available members
    const addedId = userId
    workspaceMembers.value = workspaceMembers.value // triggers computed refresh
  } catch {
    // Handled by store
  }
}

async function handleRemoveMember(userId: string) {
  if (!selectedTeam.value) return
  try {
    await teamStore.removeMember(workspaceId.value, selectedTeam.value.id, userId)
    selectedTeam.value = teamStore.currentTeam
  } catch {
    // Handled by store
  }
}
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Teams</h1>
        <p class="mt-1 text-sm text-gray-500">Organize workspace members into teams</p>
      </div>
      <button
        v-if="canManageMembers"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        @click="openCreateModal"
      >
        Create team
      </button>
    </div>

    <!-- Error banner -->
    <div
      v-if="teamStore.error"
      class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
      role="alert"
    >
      {{ teamStore.error }}
    </div>

    <!-- Loading -->
    <div v-if="teamStore.loading && !teamStore.teams.length" class="py-12 text-center text-gray-500">
      Loading teams...
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!teamStore.teams.length"
      class="rounded-xl border border-dashed border-gray-300 py-12 text-center"
    >
      <p class="text-gray-500">No teams yet. Create your first team to get started.</p>
    </div>

    <!-- Teams list + detail -->
    <div v-else class="grid gap-6" :class="selectedTeam ? 'grid-cols-2' : 'grid-cols-1'">
      <!-- Team list -->
      <div class="space-y-3">
        <div
          v-for="team in teamStore.teams"
          :key="team.id"
          class="cursor-pointer rounded-lg border bg-white p-4 transition-colors hover:border-blue-300"
          :class="selectedTeam?.id === team.id ? 'border-blue-500 ring-1 ring-blue-500' : 'border-gray-200'"
          @click="viewTeamMembers(team)"
        >
          <div class="flex items-center justify-between">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-medium text-gray-900">{{ team.name }}</h3>
                <span
                  v-if="team.is_default"
                  class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
                >
                  Default
                </span>
              </div>
              <p v-if="team.description" class="mt-1 text-sm text-gray-500">{{ team.description }}</p>
              <p class="mt-1 text-xs text-gray-400">{{ team.member_count }} member{{ team.member_count !== 1 ? 's' : '' }}</p>
            </div>
            <div class="flex gap-1">
              <span
                v-tooltip="!canManageMembers ? 'You don\'t have permission to perform this action' : undefined"
                class="inline-flex"
              >
                <button
                  class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent disabled:hover:text-gray-400"
                  :disabled="!canManageMembers"
                  title="Edit"
                  @click.stop="openEditModal(team)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </span>
              <button
                v-if="canManageMembers"
                class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600"
                title="Delete"
                @click.stop="handleDelete(team)"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Team detail panel -->
      <div v-if="selectedTeam" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-medium text-gray-900">{{ selectedTeam.name }} — Members</h3>
          <button
            v-if="canManageMembers"
            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
            @click="openAddMemberModal"
          >
            Add member
          </button>
        </div>

        <div v-if="!selectedTeam.members.length" class="py-6 text-center text-sm text-gray-500">
          No members yet
        </div>

        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="member in selectedTeam.members"
            :key="member.id"
            class="flex items-center justify-between py-2"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
              <p class="text-xs text-gray-500">{{ member.email }}</p>
            </div>
            <button
              v-if="canManageMembers"
              class="text-xs text-red-600 hover:text-red-800"
              @click="handleRemoveMember(member.user_id)"
            >
              Remove
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="closeModal"
      >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
          <h2 class="mb-4 text-lg font-bold text-gray-900">
            {{ editingTeam ? 'Edit team' : 'Create team' }}
          </h2>

          <form @submit.prevent="handleSubmit" novalidate>
            <div class="space-y-4">
              <div>
                <label for="team-name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input
                  id="team-name"
                  v-model="formName"
                  type="text"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="getFieldError('name') ? 'border-red-300' : 'border-gray-300'"
                  placeholder="e.g. Content Team"
                />
                <p v-if="getFieldError('name')" class="mt-1 text-xs text-red-600">
                  {{ getFieldError('name') }}
                </p>
              </div>

              <div>
                <label for="team-desc" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  id="team-desc"
                  v-model="formDescription"
                  rows="2"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Optional description"
                />
              </div>

              <label class="flex items-center gap-2">
                <input v-model="formIsDefault" type="checkbox" class="rounded" />
                <span class="text-sm text-gray-700">Set as default team</span>
              </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
              <button
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="closeModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                :disabled="teamStore.loading"
              >
                {{ editingTeam ? 'Save' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Add Member Modal -->
    <Teleport to="body">
      <div
        v-if="showMemberModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="closeMemberModal"
      >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
          <h2 class="mb-4 text-lg font-bold text-gray-900">Add member to team</h2>

          <div v-if="!availableMembers.length" class="py-4 text-center text-sm text-gray-500">
            All workspace members are already in this team
          </div>

          <ul v-else class="max-h-64 divide-y divide-gray-100 overflow-y-auto">
            <li
              v-for="member in availableMembers"
              :key="member.user_id"
              class="flex items-center justify-between py-2"
            >
              <div>
                <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                <p class="text-xs text-gray-500">{{ member.email }}</p>
              </div>
              <button
                class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                @click="handleAddMember(member.user_id)"
              >
                Add
              </button>
            </li>
          </ul>

          <div class="mt-4 flex justify-end">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              @click="closeMemberModal"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
