<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useContentStore } from '@/stores/content'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { contentApi } from '@/api/content'
import { parseApiError } from '@/utils/error-handler'
import type { PostData } from '@/types/content'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PostList from '@/components/content/PostList.vue'
import PostFilters from '@/components/content/PostFilters.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const contentStore = useContentStore()
const toast = useToast()
const { confirmDelete } = useConfirm()

const workspaceId = computed(() => route.params.workspaceId as string)
const filterStatus = ref('')
const filterSearch = ref('')

onMounted(() => fetchPosts())

watch([filterStatus, filterSearch], () => fetchPosts(1))

function fetchPosts(page = 1) {
  contentStore.fetchPosts(workspaceId.value, {
    page,
    status: filterStatus.value,
    search: filterSearch.value || undefined,
  })
}

function createPost() {
  router.push(`/app/w/${workspaceId.value}/posts/create`)
}

function editPost(post: PostData) {
  router.push(`/app/w/${workspaceId.value}/posts/${post.id}/edit`)
}

function duplicatePost(post: PostData) {
  contentApi
    .duplicatePost(workspaceId.value, post.id)
    .then((duplicated) => {
      contentStore.addPost(duplicated)
      toast.success('Post duplicated')
    })
    .catch((e) => toast.error(parseApiError(e).message))
}

function deletePost(post: PostData) {
  confirmDelete({
    message: 'Are you sure you want to delete this post?',
    async onAccept() {
      try {
        await contentApi.deletePost(workspaceId.value, post.id)
        contentStore.removePost(post.id)
        toast.success('Post deleted')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}
</script>

<template>
  <AppPageHeader title="Posts" description="Create and manage your social media content">
    <template #actions>
      <Button label="New Post" icon="pi pi-plus" @click="createPost" />
    </template>
  </AppPageHeader>

  <AppCard>
    <PostFilters
      v-model:status="filterStatus"
      v-model:search="filterSearch"
      class="mb-4"
    />
    <PostList
      :posts="contentStore.posts"
      :loading="contentStore.loading"
      :pagination="contentStore.pagination"
      @edit="editPost"
      @delete="deletePost"
      @duplicate="duplicatePost"
      @page="fetchPosts"
    />
  </AppCard>
</template>
