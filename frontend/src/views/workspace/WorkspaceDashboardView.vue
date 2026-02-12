<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWorkspace } from '@/composables/useWorkspace'
import { workspaceApi } from '@/api/workspace'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WorkspaceDashboardData, RecentPostData } from '@/types/dashboard'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { currentWorkspace } = useWorkspace()

const stats = ref<WorkspaceDashboardData | null>(null)
const loading = ref(true)

const workspaceId = route.params.workspaceId as string

onMounted(async () => {
  try {
    stats.value = await workspaceApi.getDashboard(workspaceId)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
})

function statusSeverity(status: string) {
  switch (status) {
    case 'published': return 'success'
    case 'scheduled': return 'info'
    case 'draft': return 'secondary'
    case 'submitted': return 'warn'
    case 'failed': return 'danger'
    default: return 'secondary'
  }
}

function navigateTo(path: string) {
  router.push(`/app/w/${workspaceId}/${path}`)
}
</script>

<template>
  <AppPageHeader
    :title="currentWorkspace?.name || 'Workspace'"
    description="Overview of your workspace activity"
  />

  <AppLoadingSkeleton v-if="loading" :lines="8" />

  <template v-else-if="stats">
    <!-- Stat Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <AppCard class="cursor-pointer transition-shadow hover:shadow-md" @click="navigateTo('posts')">
        <div class="text-center">
          <i class="pi pi-file-edit mb-2 text-2xl text-primary-500" />
          <p class="text-2xl font-bold text-gray-900">{{ stats.total_posts }}</p>
          <p class="text-sm text-gray-500">Total Posts</p>
          <div class="mt-2 flex justify-center gap-2 text-xs">
            <span class="text-green-600">{{ stats.posts_published }} published</span>
            <span class="text-blue-600">{{ stats.posts_scheduled }} scheduled</span>
          </div>
        </div>
      </AppCard>
      <AppCard class="cursor-pointer transition-shadow hover:shadow-md" @click="navigateTo('social-accounts')">
        <div class="text-center">
          <i class="pi pi-share-alt mb-2 text-2xl text-green-500" />
          <p class="text-2xl font-bold text-gray-900">{{ stats.social_accounts_count }}</p>
          <p class="text-sm text-gray-500">Social Accounts</p>
        </div>
      </AppCard>
      <AppCard class="cursor-pointer transition-shadow hover:shadow-md" @click="navigateTo('inbox')">
        <div class="text-center">
          <i class="pi pi-inbox mb-2 text-2xl text-orange-500" />
          <p class="text-2xl font-bold text-gray-900">{{ stats.inbox_unread_count }}</p>
          <p class="text-sm text-gray-500">Unread Inbox</p>
        </div>
      </AppCard>
      <AppCard>
        <div class="text-center">
          <i class="pi pi-users mb-2 text-2xl text-purple-500" />
          <p class="text-2xl font-bold text-gray-900">{{ stats.member_count }}</p>
          <p class="text-sm text-gray-500">Members</p>
        </div>
      </AppCard>
    </div>

    <!-- Pending Approvals Alert -->
    <div v-if="stats.pending_approvals > 0" class="mb-6 flex items-center gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
      <i class="pi pi-exclamation-triangle text-yellow-600" />
      <span class="text-sm text-yellow-800">
        {{ stats.pending_approvals }} post{{ stats.pending_approvals > 1 ? 's' : '' }} pending approval
      </span>
      <Button label="Review" size="small" severity="warn" class="ml-auto" @click="navigateTo('approvals')" />
    </div>

    <!-- Quick Actions + Recent Posts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Recent Posts -->
      <div class="lg:col-span-2">
        <h2 class="mb-3 text-lg font-semibold text-gray-900">Recent Posts</h2>
        <AppCard v-if="stats.recent_posts.length > 0">
          <div class="divide-y divide-gray-100">
            <div
              v-for="post in stats.recent_posts"
              :key="post.id"
              class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-900">
                  {{ post.content_excerpt || 'No content' }}
                </p>
                <p class="text-xs text-gray-400">{{ new Date(post.created_at).toLocaleDateString() }}</p>
              </div>
              <Tag :value="post.status" :severity="statusSeverity(post.status)" class="!text-xs capitalize" />
            </div>
          </div>
        </AppCard>
        <p v-else class="text-sm text-gray-400">No posts yet.</p>
      </div>

      <!-- Quick Actions -->
      <div>
        <h2 class="mb-3 text-lg font-semibold text-gray-900">Quick Actions</h2>
        <div class="space-y-2">
          <Button label="Create Post" icon="pi pi-plus" class="w-full" @click="navigateTo('posts/create')" />
          <Button label="View Inbox" icon="pi pi-inbox" severity="secondary" class="w-full" @click="navigateTo('inbox')" />
          <Button label="Analytics" icon="pi pi-chart-bar" severity="secondary" class="w-full" @click="navigateTo('analytics')" />
          <Button label="Manage Accounts" icon="pi pi-cog" severity="secondary" class="w-full" @click="navigateTo('social-accounts')" />
        </div>
      </div>
    </div>
  </template>
</template>
