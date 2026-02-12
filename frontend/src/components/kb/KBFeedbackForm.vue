<script setup lang="ts">
import { ref } from 'vue'
import { kbApi } from '@/api/kb'
import { useToast } from '@/composables/useToast'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

const props = defineProps<{
  articleId: string
}>()

const toast = useToast()
const submitted = ref(false)
const submitting = ref(false)
const isHelpful = ref<boolean | null>(null)
const comment = ref('')
const category = ref('')

const categories = [
  { label: 'Unclear', value: 'unclear' },
  { label: 'Outdated', value: 'outdated' },
  { label: 'Incomplete', value: 'incomplete' },
  { label: 'Incorrect', value: 'incorrect' },
  { label: 'Other', value: 'other' },
]

async function submit() {
  if (isHelpful.value === null) return
  submitting.value = true
  try {
    await kbApi.submitFeedback(props.articleId, {
      is_helpful: isHelpful.value,
      category: category.value || undefined,
      comment: comment.value || undefined,
    })
    submitted.value = true
    toast.success('Feedback submitted')
  } catch {
    toast.error('Failed to submit feedback')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div v-if="!submitted" class="space-y-4">
    <div class="flex gap-3">
      <Button
        :label="isHelpful === true ? 'Helpful!' : 'Helpful'"
        icon="pi pi-thumbs-up"
        :severity="isHelpful === true ? 'success' : 'secondary'"
        :outlined="isHelpful !== true"
        size="small"
        @click="isHelpful = true"
      />
      <Button
        :label="isHelpful === false ? 'Not Helpful' : 'Not Helpful'"
        icon="pi pi-thumbs-down"
        :severity="isHelpful === false ? 'danger' : 'secondary'"
        :outlined="isHelpful !== false"
        size="small"
        @click="isHelpful = false"
      />
    </div>

    <template v-if="isHelpful === false">
      <Select
        v-model="category"
        :options="categories"
        option-label="label"
        option-value="value"
        placeholder="What's the issue?"
        class="w-full"
      />
      <Textarea v-model="comment" placeholder="Tell us more..." rows="3" class="w-full" />
    </template>

    <Button
      v-if="isHelpful !== null"
      label="Submit"
      :loading="submitting"
      size="small"
      @click="submit"
    />
  </div>
  <p v-else class="text-sm text-green-600">
    <i class="pi pi-check-circle mr-1" />Thanks for your feedback!
  </p>
</template>
