<script setup lang="ts">
import { ref } from 'vue'
import { feedbackApi } from '@/api/feedback'
import Button from 'primevue/button'

const props = defineProps<{
  feedbackId: string
  voteCount: number
  userVote: number | null
}>()

const emit = defineEmits<{
  voted: [newCount: number]
}>()

const count = ref(props.voteCount)
const voted = ref(props.userVote !== null)
const loading = ref(false)

async function toggleVote() {
  loading.value = true
  try {
    if (voted.value) {
      await feedbackApi.removeVote(props.feedbackId)
      count.value--
      voted.value = false
    } else {
      await feedbackApi.vote(props.feedbackId, { vote_type: 'upvote' })
      count.value++
      voted.value = true
    }
    emit('voted', count.value)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Button
    :icon="voted ? 'pi pi-chevron-up' : 'pi pi-chevron-up'"
    :label="String(count)"
    :severity="voted ? 'primary' : 'secondary'"
    :outlined="!voted"
    size="small"
    :loading="loading"
    @click="toggleVote"
  />
</template>
