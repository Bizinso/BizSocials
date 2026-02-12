<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { workspaceTaskApi } from '@/api/collaboration'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WorkspaceTaskData } from '@/types/collaboration'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const todoTasks = ref<WorkspaceTaskData[]>([])
const inProgressTasks = ref<WorkspaceTaskData[]>([])
const doneTasks = ref<WorkspaceTaskData[]>([])
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref({
  title: '',
  description: '',
  assigned_to_user_id: '',
  due_date: '',
  priority: 'medium' as string,
})

const priorityColors: Record<string, string> = {
  low: 'bg-blue-50 text-blue-700',
  medium: 'bg-yellow-50 text-yellow-700',
  high: 'bg-red-50 text-red-700',
}

onMounted(() => fetchAllTasks())

async function fetchAllTasks() {
  loading.value = true
  try {
    const [todoRes, inProgRes, doneRes] = await Promise.all([
      workspaceTaskApi.list(workspaceId.value, { status: 'todo' }),
      workspaceTaskApi.list(workspaceId.value, { status: 'in_progress' }),
      workspaceTaskApi.list(workspaceId.value, { status: 'done' }),
    ])
    todoTasks.value = todoRes.data
    inProgressTasks.value = inProgRes.data
    doneTasks.value = doneRes.data
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createTask() {
  saving.value = true
  try {
    const payload: Record<string, unknown> = { title: form.value.title, priority: form.value.priority }
    if (form.value.description) payload.description = form.value.description
    if (form.value.assigned_to_user_id) payload.assigned_to_user_id = form.value.assigned_to_user_id
    if (form.value.due_date) payload.due_date = form.value.due_date
    await workspaceTaskApi.create(workspaceId.value, payload as any)
    toast.success('Task created')
    showCreate.value = false
    form.value = { title: '', description: '', assigned_to_user_id: '', due_date: '', priority: 'medium' }
    fetchAllTasks()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function completeTask(task: WorkspaceTaskData) {
  try {
    await workspaceTaskApi.complete(workspaceId.value, task.id)
    toast.success('Task completed')
    fetchAllTasks()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteTask(task: WorkspaceTaskData) {
  if (!confirm(`Delete "${task.title}"?`)) return
  try {
    await workspaceTaskApi.delete(workspaceId.value, task.id)
    toast.success('Deleted')
    fetchAllTasks()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Tasks" description="Kanban-style task board for your workspace">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" /> New Task
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createTask">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Title *</label>
        <input v-model="form.title" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
        <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Assign To (User ID)</label>
          <input v-model="form.assigned_to_user_id" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="User ID" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Due Date</label>
          <input v-model="form.due_date" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
          <select v-model="form.priority" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <div v-if="loading" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>

  <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <!-- To Do Column -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wide">To Do <span class="ml-1 text-gray-400">({{ todoTasks.length }})</span></h3>
      <div class="space-y-2">
        <AppCard v-for="task in todoTasks" :key="task.id" class="!p-3">
          <div class="flex items-start justify-between">
            <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
            <span :class="priorityColors[task.priority]" class="ml-2 shrink-0 rounded px-1.5 py-0.5 text-xs font-medium">{{ task.priority }}</span>
          </div>
          <p v-if="task.description" class="mt-1 text-xs text-gray-500 line-clamp-2">{{ task.description }}</p>
          <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-400">
              <span v-if="task.assigned_to_name"><i class="pi pi-user mr-0.5" />{{ task.assigned_to_name }}</span>
              <span v-if="task.due_date"><i class="pi pi-calendar mr-0.5" />{{ task.due_date }}</span>
            </div>
            <div class="flex items-center gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-green-50 hover:text-green-500" title="Complete" @click="completeTask(task)"><i class="pi pi-check text-xs" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteTask(task)"><i class="pi pi-trash text-xs" /></button>
            </div>
          </div>
        </AppCard>
        <div v-if="todoTasks.length === 0" class="rounded-lg border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400">No tasks</div>
      </div>
    </div>

    <!-- In Progress Column -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wide">In Progress <span class="ml-1 text-gray-400">({{ inProgressTasks.length }})</span></h3>
      <div class="space-y-2">
        <AppCard v-for="task in inProgressTasks" :key="task.id" class="!p-3">
          <div class="flex items-start justify-between">
            <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
            <span :class="priorityColors[task.priority]" class="ml-2 shrink-0 rounded px-1.5 py-0.5 text-xs font-medium">{{ task.priority }}</span>
          </div>
          <p v-if="task.description" class="mt-1 text-xs text-gray-500 line-clamp-2">{{ task.description }}</p>
          <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-400">
              <span v-if="task.assigned_to_name"><i class="pi pi-user mr-0.5" />{{ task.assigned_to_name }}</span>
              <span v-if="task.due_date"><i class="pi pi-calendar mr-0.5" />{{ task.due_date }}</span>
            </div>
            <div class="flex items-center gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-green-50 hover:text-green-500" title="Complete" @click="completeTask(task)"><i class="pi pi-check text-xs" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteTask(task)"><i class="pi pi-trash text-xs" /></button>
            </div>
          </div>
        </AppCard>
        <div v-if="inProgressTasks.length === 0" class="rounded-lg border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400">No tasks</div>
      </div>
    </div>

    <!-- Done Column -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wide">Done <span class="ml-1 text-gray-400">({{ doneTasks.length }})</span></h3>
      <div class="space-y-2">
        <AppCard v-for="task in doneTasks" :key="task.id" class="!p-3 opacity-75">
          <div class="flex items-start justify-between">
            <p class="text-sm font-medium text-gray-900 line-through">{{ task.title }}</p>
            <span :class="priorityColors[task.priority]" class="ml-2 shrink-0 rounded px-1.5 py-0.5 text-xs font-medium">{{ task.priority }}</span>
          </div>
          <div class="mt-2 flex items-center justify-between">
            <span v-if="task.completed_at" class="text-xs text-gray-400"><i class="pi pi-check-circle mr-0.5" />{{ new Date(task.completed_at).toLocaleDateString() }}</span>
            <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteTask(task)"><i class="pi pi-trash text-xs" /></button>
          </div>
        </AppCard>
        <div v-if="doneTasks.length === 0" class="rounded-lg border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400">No tasks</div>
      </div>
    </div>
  </div>
</template>
