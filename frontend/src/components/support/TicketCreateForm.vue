<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { supportApi } from '@/api/support'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { SupportCategoryData, CreateTicketRequest } from '@/types/support'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'

const router = useRouter()
const toast = useToast()
const submitting = ref(false)
const categories = ref<SupportCategoryData[]>([])

const form = ref({
  subject: '',
  description: '',
  type: 'question',
  priority: 'medium',
  category_id: '',
})
const attachments = ref<File[]>([])

const types = [
  { label: 'Question', value: 'question' },
  { label: 'Problem', value: 'problem' },
  { label: 'Feature Request', value: 'feature_request' },
  { label: 'Bug Report', value: 'bug_report' },
  { label: 'Billing', value: 'billing' },
  { label: 'Account', value: 'account' },
  { label: 'Other', value: 'other' },
]

const priorities = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' },
]

onMounted(async () => {
  categories.value = await supportApi.listCategories()
})

function onFileSelect(event: { files: File[] }) {
  attachments.value = event.files
}

function removeFile(index: number) {
  attachments.value.splice(index, 1)
}

async function submit() {
  if (!form.value.subject.trim() || !form.value.description.trim()) return
  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('subject', form.value.subject)
    formData.append('description', form.value.description)
    formData.append('type', form.value.type)
    formData.append('priority', form.value.priority)
    if (form.value.category_id) {
      formData.append('category_id', form.value.category_id)
    }
    attachments.value.forEach((file, i) => {
      formData.append(`attachments[${i}]`, file)
    })

    const ticket = await supportApi.createTicket(formData as unknown as CreateTicketRequest)
    toast.success('Ticket created successfully!')
    router.push({ name: 'support-ticket-detail', params: { ticketId: ticket.id } })
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
      <label class="mb-1 block text-sm font-medium text-gray-700">Subject *</label>
      <InputText v-model="form.subject" placeholder="Brief description of your issue" class="w-full" />
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Description *</label>
      <Textarea v-model="form.description" placeholder="Describe your issue in detail..." rows="6" class="w-full" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
        <Select v-model="form.type" :options="types" option-label="label" option-value="value" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
        <Select v-model="form.priority" :options="priorities" option-label="label" option-value="value" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
        <Select
          v-model="form.category_id"
          :options="categories"
          option-label="name"
          option-value="id"
          placeholder="Select category"
          class="w-full"
        />
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Attachments</label>
      <FileUpload
        mode="basic"
        multiple
        :auto="false"
        accept="image/*,.pdf,.doc,.docx,.txt,.csv,.xlsx"
        :max-file-size="10000000"
        choose-label="Choose Files"
        @select="onFileSelect"
      />
      <div v-if="attachments.length > 0" class="mt-2 space-y-1">
        <div
          v-for="(file, index) in attachments"
          :key="index"
          class="flex items-center justify-between rounded bg-gray-50 px-3 py-1.5 text-sm"
        >
          <span class="truncate">{{ file.name }}</span>
          <button type="button" class="text-red-500 hover:text-red-700" @click="removeFile(index)">
            <i class="pi pi-times text-xs" />
          </button>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <Button type="submit" label="Create Ticket" icon="pi pi-send" :loading="submitting" />
      <Button label="Cancel" severity="secondary" @click="router.back()" />
    </div>
  </form>
</template>
