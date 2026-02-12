<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { contentApi } from '@/api/content'
import type { PostData } from '@/types/content'
import type { PaginationMeta } from '@/types/api'
import PostStatusBadge from './PostStatusBadge.vue'
import ApprovalActions from './ApprovalActions.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { truncate, formatRelative } from '@/utils/formatters'
import Paginator from 'primevue/paginator'

const props = defineProps<{
  workspaceId: string
}>()

const posts = ref<PostData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(() => fetchApprovals())

async function fetchApprovals(page = 1) {
  loading.value = true
  try {
    const response = await contentApi.listApprovals(props.workspaceId, { page })
    posts.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

function onDecided(post: PostData) {
  posts.value = posts.value.filter((p) => p.id !== post.id)
}

function onPageChange(event: any) {
  fetchApprovals(event.page + 1)
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="3" :count="3" />

    <template v-else-if="posts.length > 0">
      <div class="space-y-3">
        <div
          v-for="post in posts"
          :key="post.id"
          class="rounded-lg border border-gray-200 bg-white p-4"
        >
          <div class="mb-2 flex items-start justify-between">
            <div class="min-w-0 flex-1">
              <div class="mb-1 flex items-center gap-2">
                <PostStatusBadge :status="post.status" />
                <span class="text-xs text-gray-400">{{ formatRelative(post.created_at) }}</span>
                <span v-if="post.author_name" class="text-xs text-gray-500">by {{ post.author_name }}</span>
              </div>
              <p class="text-sm text-gray-900">
                {{ post.content_text ? truncate(post.content_text, 200) : '(No content)' }}
              </p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 text-xs text-gray-500">
              <span v-if="post.target_count > 0">
                <i class="pi pi-share-alt mr-1" />{{ post.target_count }} target{{ post.target_count !== 1 ? 's' : '' }}
              </span>
              <span v-if="post.media_count > 0">
                <i class="pi pi-image mr-1" />{{ post.media_count }} media
              </span>
            </div>
            <ApprovalActions :workspace-id="workspaceId" :post="post" @decided="onDecided" />
          </div>
        </div>
      </div>

      <Paginator
        v-if="pagination && pagination.last_page > 1"
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        class="mt-4"
        @page="onPageChange"
      />
    </template>

    <AppEmptyState
      v-else
      title="No posts awaiting approval"
      description="Posts submitted for review will appear here."
      icon="pi pi-check-circle"
    />
  </div>
</template>
