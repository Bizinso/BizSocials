<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { PostData } from '@/types/content'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PostCalendar from '@/components/content/PostCalendar.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()

const workspaceId = computed(() => route.params.workspaceId as string)

function onSelectPost(post: PostData) {
  router.push(`/app/w/${workspaceId.value}/posts/${post.id}/edit`)
}

function onSelectDate(date: string) {
  // could open a day-detail panel in the future
}

function createPost() {
  router.push(`/app/w/${workspaceId.value}/posts/create`)
}
</script>

<template>
  <AppPageHeader title="Content Calendar" description="View scheduled and published posts on a calendar">
    <template #actions>
      <Button label="New Post" icon="pi pi-plus" @click="createPost" />
    </template>
  </AppPageHeader>

  <AppCard>
    <PostCalendar
      :workspace-id="workspaceId"
      @select-post="onSelectPost"
      @select-date="onSelectDate"
    />
  </AppCard>
</template>
