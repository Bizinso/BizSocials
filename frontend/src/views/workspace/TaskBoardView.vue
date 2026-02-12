<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { workspaceTaskApi } from '@/api/collaboration'
import type { WorkspaceTaskData } from '@/types/collaboration'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const allTasks = ref<WorkspaceTaskData[]>([])
const loading = ref(false)
const showCreateForm = ref(false)

// Filters
const filterAssignee = ref('')
const filterPriority = ref('')

// Form state
const formTitle = ref('')
const formDescription = ref('')
const formAssignee = ref('')
const formDueDate = ref('')
const formPriority = ref('medium')

const todoTasks = computed(() =>
  allTasks.value.filter((t) => t.status === 'todo'),
)

const inProgressTasks = computed(() =>
  allTasks.value.filter((t) => t.status === 'in_progress'),
)

const doneTasks = computed(() =>
  allTasks.value.filter((t) => t.status === 'done'),
)

onMounted(() => fetchTasks())

async function fetchTasks() {
  loading.value = true
  try {
    const params: Record<string, unknown> = { per_page: 100 }
    if (filterAssignee.value) params.assigned_to_user_id = filterAssignee.value
    if (filterPriority.value) params.priority = filterPriority.value

    const response = await workspaceTaskApi.list(workspaceId.value, params)
    allTasks.value = response.data
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  formTitle.value = ''
  formDescription.value = ''
  formAssignee.value = ''
  formDueDate.value = ''
  formPriority.value = 'medium'
}

async function createTask() {
  try {
    const data: Record<string, unknown> = {
      title: formTitle.value,
      priority: formPriority.value,
    }
    if (formDescription.value) data.description = formDescription.value
    if (formAssignee.value) data.assigned_to_user_id = formAssignee.value
    if (formDueDate.value) data.due_date = formDueDate.value

    await workspaceTaskApi.create(workspaceId.value, data as never)
    toast.success('Task created')
    showCreateForm.value = false
    resetForm()
    await fetchTasks()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function updateTaskStatus(task: WorkspaceTaskData, newStatus: string) {
  try {
    await workspaceTaskApi.update(workspaceId.value, task.id, { status: newStatus })
    task.status = newStatus as WorkspaceTaskData['status']
    toast.success('Task updated')
    await fetchTasks()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function completeTask(task: WorkspaceTaskData) {
  try {
    await workspaceTaskApi.complete(workspaceId.value, task.id)
    toast.success('Task completed')
    await fetchTasks()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function deleteTask(task: WorkspaceTaskData) {
  if (!confirm(`Delete task "${task.title}"?`)) return
  try {
    await workspaceTaskApi.delete(workspaceId.value, task.id)
    toast.success('Task deleted')
    await fetchTasks()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function priorityColor(priority: string): string {
  switch (priority) {
    case 'high': return 'bg-red-100 text-red-800'
    case 'medium': return 'bg-yellow-100 text-yellow-800'
    case 'low': return 'bg-green-100 text-green-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function isOverdue(task: WorkspaceTaskData): boolean {
  if (!task.due_date || task.status === 'done') return false
  return new Date(task.due_date) < new Date()
}
</script>

<template>
  <AppPageHeader title="Tasks" description="Manage workspace tasks with a kanban board">
    <template #actions>
      <button
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        @click="showCreateForm = !showCreateForm"
      >
        <i class="pi pi-plus text-sm"></i>
        New Task
      </button>
    </template>
  </AppPageHeader>

  <!-- Filters -->
  <div class="mb-4 flex items-center gap-4">
    <div>
      <label class="block text-xs font-medium text-gray-500">Assignee ID</label>
      <input
        v-model="filterAssignee"
        type="text"
        placeholder="Filter by assignee"
        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @change="fetchTasks()"
      />
    </div>
    <div>
      <label class="block text-xs font-medium text-gray-500">Priority</label>
      <select
        v-model="filterPriority"
        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @change="fetchTasks()"
      >
        <option value="">All</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
      </select>
    </div>
  </div>

  <!-- Create Task Form -->
  <AppCard v-if="showCreateForm" class="mb-6">
    <div class="space-y-4">
      <h3 class="text-lg font-semibold text-gray-900">Create Task</h3>
      <div>
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input
          v-model="formTitle"
          type="text"
          placeholder="Task title"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          v-model="formDescription"
          rows="3"
          placeholder="Optional description"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        ></textarea>
      </div>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Assign To (User ID)</label>
          <input
            v-model="formAssignee"
            type="text"
            placeholder="User UUID"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Due Date</label>
          <input
            v-model="formDueDate"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Priority</label>
          <select
            v-model="formPriority"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          @click="createTask"
        >
          Create
        </button>
        <button
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="showCreateForm = false; resetForm()"
        >
          Cancel
        </button>
      </div>
    </div>
  </AppCard>

  <!-- Kanban Board -->
  <div v-if="loading" class="py-8 text-center text-gray-500">Loading tasks...</div>

  <div v-else class="grid grid-cols-3 gap-4">
    <!-- To Do Column -->
    <div class="rounded-lg bg-gray-50 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">
          <i class="pi pi-circle mr-1 text-gray-400"></i>
          To Do
        </h3>
        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">
          {{ todoTasks.length }}
        </span>
      </div>
      <div class="space-y-3">
        <div
          v-for="task in todoTasks"
          :key="task.id"
          class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm"
        >
          <div class="mb-2 flex items-start justify-between">
            <h4 class="text-sm font-medium text-gray-900">{{ task.title }}</h4>
            <span :class="priorityColor(task.priority)" class="rounded-full px-2 py-0.5 text-xs font-medium">
              {{ task.priority }}
            </span>
          </div>
          <div v-if="task.assigned_to_name" class="mb-1 text-xs text-gray-500">
            <i class="pi pi-user mr-1"></i>{{ task.assigned_to_name }}
          </div>
          <div v-if="task.due_date" class="mb-2 text-xs" :class="isOverdue(task) ? 'text-red-600 font-medium' : 'text-gray-500'">
            <i class="pi pi-calendar mr-1"></i>{{ task.due_date }}
            <span v-if="isOverdue(task)"> (overdue)</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="text-xs text-blue-600 hover:text-blue-800"
              @click="updateTaskStatus(task, 'in_progress')"
            >
              Start
            </button>
            <button
              class="text-xs text-red-600 hover:text-red-800"
              @click="deleteTask(task)"
            >
              Delete
            </button>
          </div>
        </div>
        <div v-if="todoTasks.length === 0" class="py-4 text-center text-xs text-gray-400">
          No tasks
        </div>
      </div>
    </div>

    <!-- In Progress Column -->
    <div class="rounded-lg bg-blue-50 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-blue-700">
          <i class="pi pi-spinner mr-1 text-blue-400"></i>
          In Progress
        </h3>
        <span class="rounded-full bg-blue-200 px-2 py-0.5 text-xs font-medium text-blue-600">
          {{ inProgressTasks.length }}
        </span>
      </div>
      <div class="space-y-3">
        <div
          v-for="task in inProgressTasks"
          :key="task.id"
          class="rounded-lg border border-blue-200 bg-white p-3 shadow-sm"
        >
          <div class="mb-2 flex items-start justify-between">
            <h4 class="text-sm font-medium text-gray-900">{{ task.title }}</h4>
            <span :class="priorityColor(task.priority)" class="rounded-full px-2 py-0.5 text-xs font-medium">
              {{ task.priority }}
            </span>
          </div>
          <div v-if="task.assigned_to_name" class="mb-1 text-xs text-gray-500">
            <i class="pi pi-user mr-1"></i>{{ task.assigned_to_name }}
          </div>
          <div v-if="task.due_date" class="mb-2 text-xs" :class="isOverdue(task) ? 'text-red-600 font-medium' : 'text-gray-500'">
            <i class="pi pi-calendar mr-1"></i>{{ task.due_date }}
            <span v-if="isOverdue(task)"> (overdue)</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="text-xs text-gray-600 hover:text-gray-800"
              @click="updateTaskStatus(task, 'todo')"
            >
              Back to Todo
            </button>
            <button
              class="text-xs text-green-600 hover:text-green-800"
              @click="completeTask(task)"
            >
              Complete
            </button>
            <button
              class="text-xs text-red-600 hover:text-red-800"
              @click="deleteTask(task)"
            >
              Delete
            </button>
          </div>
        </div>
        <div v-if="inProgressTasks.length === 0" class="py-4 text-center text-xs text-gray-400">
          No tasks
        </div>
      </div>
    </div>

    <!-- Done Column -->
    <div class="rounded-lg bg-green-50 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-green-700">
          <i class="pi pi-check-circle mr-1 text-green-400"></i>
          Done
        </h3>
        <span class="rounded-full bg-green-200 px-2 py-0.5 text-xs font-medium text-green-600">
          {{ doneTasks.length }}
        </span>
      </div>
      <div class="space-y-3">
        <div
          v-for="task in doneTasks"
          :key="task.id"
          class="rounded-lg border border-green-200 bg-white p-3 shadow-sm"
        >
          <div class="mb-2 flex items-start justify-between">
            <h4 class="text-sm font-medium text-gray-500 line-through">{{ task.title }}</h4>
            <span :class="priorityColor(task.priority)" class="rounded-full px-2 py-0.5 text-xs font-medium">
              {{ task.priority }}
            </span>
          </div>
          <div v-if="task.assigned_to_name" class="mb-1 text-xs text-gray-400">
            <i class="pi pi-user mr-1"></i>{{ task.assigned_to_name }}
          </div>
          <div v-if="task.completed_at" class="mb-2 text-xs text-gray-400">
            Completed {{ new Date(task.completed_at).toLocaleDateString() }}
          </div>
          <div class="flex items-center gap-2">
            <button
              class="text-xs text-gray-600 hover:text-gray-800"
              @click="updateTaskStatus(task, 'todo')"
            >
              Reopen
            </button>
            <button
              class="text-xs text-red-600 hover:text-red-800"
              @click="deleteTask(task)"
            >
              Delete
            </button>
          </div>
        </div>
        <div v-if="doneTasks.length === 0" class="py-4 text-center text-xs text-gray-400">
          No tasks
        </div>
      </div>
    </div>
  </div>
</template>
