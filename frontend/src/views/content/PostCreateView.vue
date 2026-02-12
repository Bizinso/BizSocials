<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { PostData } from '@/types/content'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PostEditor from '@/components/content/PostEditor.vue'

const route = useRoute()
const router = useRouter()

const workspaceId = computed(() => route.params.workspaceId as string)

function onSaved(post: PostData) {
  router.push(`/app/w/${workspaceId.value}/posts/${post.id}/edit`)
}

function onCancelled() {
  router.push(`/app/w/${workspaceId.value}/posts`)
}
</script>

<template>
  <AppPageHeader title="Create Post" description="Compose a new social media post" />

  <AppCard>
    <PostEditor :workspace-id="workspaceId" @saved="onSaved" @cancelled="onCancelled" />
  </AppCard>
</template>
