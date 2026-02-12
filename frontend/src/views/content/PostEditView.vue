<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PostEditor from '@/components/content/PostEditor.vue'

const route = useRoute()
const router = useRouter()

const workspaceId = computed(() => route.params.workspaceId as string)
const postId = computed(() => route.params.postId as string)

function onSaved() {
  // stay on edit page
}

function onCancelled() {
  router.push(`/app/w/${workspaceId.value}/posts`)
}
</script>

<template>
  <AppPageHeader title="Edit Post" description="Update your social media post" />

  <AppCard>
    <PostEditor
      :workspace-id="workspaceId"
      :post-id="postId"
      @saved="onSaved"
      @cancelled="onCancelled"
    />
  </AppCard>
</template>
