<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { feedbackApi } from '@/api/feedback'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'

const router = useRouter()
const toast = useToast()
const submitting = ref(false)

const form = ref({
  title: '',
  description: '',
  type: 'feature_request',
  category: '',
  name: '',
  email: '',
  is_anonymous: false,
})

const types = [
  { label: 'Feature Request', value: 'feature_request' },
  { label: 'Improvement', value: 'improvement' },
  { label: 'Bug Report', value: 'bug_report' },
  { label: 'Integration Request', value: 'integration_request' },
  { label: 'UX Feedback', value: 'ux_feedback' },
  { label: 'Other', value: 'other' },
]

const categories = [
  { label: 'Publishing', value: 'publishing' },
  { label: 'Scheduling', value: 'scheduling' },
  { label: 'Analytics', value: 'analytics' },
  { label: 'Inbox', value: 'inbox' },
  { label: 'Team Collaboration', value: 'team_collaboration' },
  { label: 'Integrations', value: 'integrations' },
  { label: 'Billing', value: 'billing' },
  { label: 'General', value: 'general' },
]

async function submit() {
  if (!form.value.title.trim() || !form.value.description.trim()) return
  submitting.value = true
  try {
    const feedback = await feedbackApi.submit({
      title: form.value.title,
      description: form.value.description,
      type: form.value.type,
      category: form.value.category || undefined,
      name: form.value.name || undefined,
      email: form.value.email || undefined,
      is_anonymous: form.value.is_anonymous,
    })
    toast.success('Feedback submitted successfully!')
    router.push({ name: 'feedback-detail', params: { feedbackId: feedback.id } })
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Title *</label>
      <InputText v-model="form.title" placeholder="Brief summary of your feedback" class="w-full" />
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Description *</label>
      <Textarea v-model="form.description" placeholder="Describe your feedback in detail..." rows="5" class="w-full" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
        <Select v-model="form.type" :options="types" option-label="label" option-value="value" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
        <Select v-model="form.category" :options="categories" option-label="label" option-value="value" placeholder="Select category" class="w-full" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Your Name</label>
        <InputText v-model="form.name" placeholder="Optional" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
        <InputText v-model="form.email" type="email" placeholder="Optional — for follow-up" class="w-full" />
      </div>
    </div>

    <div class="flex items-center gap-2">
      <Checkbox v-model="form.is_anonymous" :binary="true" input-id="anonymous" />
      <label for="anonymous" class="text-sm text-gray-700">Submit anonymously</label>
    </div>

    <div class="flex gap-3">
      <Button type="submit" label="Submit Feedback" icon="pi pi-send" :loading="submitting" />
      <Button label="Cancel" severity="secondary" @click="router.back()" />
    </div>
  </form>
</template>
