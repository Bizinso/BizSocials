<script setup lang="ts">
import { ref, watch } from 'vue'
import type { PostData } from '@/types/content'
import type { PaginationMeta } from '@/types/api'
import PostListItem from './PostListItem.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Paginator from 'primevue/paginator'

const props = defineProps<{
  posts: PostData[]
  loading: boolean
  pagination: PaginationMeta | null
}>()

const emit = defineEmits<{
  edit: [post: PostData]
  delete: [post: PostData]
  duplicate: [post: PostData]
  page: [page: number]
}>()

function onPageChange(event: any) {
  emit('page', event.page + 1)
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="4" :count="3" />

    <template v-else-if="posts.length > 0">
      <div class="space-y-3">
        <PostListItem
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @edit="emit('edit', $event)"
          @delete="emit('delete', $event)"
          @duplicate="emit('duplicate', $event)"
        />
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
      title="No posts yet"
      description="Create your first post to get started."
      icon="pi pi-file-edit"
    />
  </div>
</template>
