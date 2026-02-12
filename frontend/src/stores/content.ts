import { defineStore } from 'pinia'
import { ref } from 'vue'
import { contentApi } from '@/api/content'
import type { PostData } from '@/types/content'
import type { PaginationMeta } from '@/types/api'

export const useContentStore = defineStore('content', () => {
  const posts = ref<PostData[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  async function fetchPosts(
    workspaceId: string,
    params?: { page?: number; per_page?: number; status?: string; search?: string },
  ) {
    loading.value = true
    try {
      const response = await contentApi.listPosts(workspaceId, params)
      posts.value = response.data
      pagination.value = response.meta
    } finally {
      loading.value = false
    }
  }

  function addPost(post: PostData) {
    posts.value.unshift(post)
  }

  function updatePost(post: PostData) {
    const index = posts.value.findIndex((p) => p.id === post.id)
    if (index !== -1) {
      posts.value[index] = post
    }
  }

  function removePost(id: string) {
    posts.value = posts.value.filter((p) => p.id !== id)
  }

  function clear() {
    posts.value = []
    pagination.value = null
  }

  return {
    posts,
    pagination,
    loading,
    fetchPosts,
    addPost,
    updatePost,
    removePost,
    clear,
  }
})
