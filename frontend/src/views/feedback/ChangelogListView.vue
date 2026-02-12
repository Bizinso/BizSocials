<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ChangelogList from '@/components/feedback/ChangelogList.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { feedbackApi } from '@/api/feedback'
import type { ReleaseNoteData } from '@/types/feedback'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const releases = ref<ReleaseNoteData[]>([])
const loading = ref(true)
const subscribeEmail = ref('')
const subscribing = ref(false)

onMounted(async () => {
  try {
    const result = await feedbackApi.listChangelog()
    releases.value = result.data
  } finally {
    loading.value = false
  }
})

async function subscribe() {
  if (!subscribeEmail.value) return
  subscribing.value = true
  try {
    await feedbackApi.subscribeChangelog({ email: subscribeEmail.value })
    toast.success('Subscribed to changelog updates!')
    subscribeEmail.value = ''
  } catch {
    toast.error('Failed to subscribe')
  } finally {
    subscribing.value = false
  }
}
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">Changelog</h1>
        <p class="mt-2 text-gray-600">Stay up to date with the latest changes and improvements</p>
      </div>

      <!-- Subscribe -->
      <div class="mb-10 rounded-lg bg-gray-50 p-4 text-center">
        <p class="mb-3 text-sm font-medium text-gray-700">Get notified about new releases</p>
        <form class="mx-auto flex max-w-md gap-2" @submit.prevent="subscribe">
          <InputText v-model="subscribeEmail" type="email" placeholder="your@email.com" class="flex-1" />
          <Button type="submit" label="Subscribe" size="small" :loading="subscribing" />
        </form>
      </div>

      <AppLoadingSkeleton v-if="loading" :lines="8" />
      <ChangelogList v-else :releases="releases" />
    </div>
  </PublicLayout>
</template>
