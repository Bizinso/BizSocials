<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import FeedbackList from '@/components/feedback/FeedbackList.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'
import { feedbackApi } from '@/api/feedback'
import type { FeedbackData } from '@/types/feedback'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

const router = useRouter()
const items = ref<FeedbackData[]>([])
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)
const search = ref('')
const statusFilter = ref('')
const sortBy = ref('newest')

const statuses = [
  { label: 'All Statuses', value: '' },
  { label: 'New', value: 'new' },
  { label: 'Under Review', value: 'under_review' },
  { label: 'Planned', value: 'planned' },
  { label: 'Shipped', value: 'shipped' },
]

const sorts = [
  { label: 'Newest', value: 'newest' },
  { label: 'Most Voted', value: 'top_voted' },
  { label: 'Most Commented', value: 'most_commented' },
]

async function fetchItems() {
  loading.value = true
  try {
    const result = await feedbackApi.list({
      page: currentPage.value,
      search: search.value || undefined,
      status: statusFilter.value || undefined,
      sort: sortBy.value,
    })
    items.value = result.data
    totalRecords.value = result.meta.total
  } finally {
    loading.value = false
  }
}

onMounted(fetchItems)

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchItems()
}
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Feedback</h1>
          <p class="mt-1 text-gray-600">Help us improve BizSocials</p>
        </div>
        <Button label="Submit Feedback" icon="pi pi-plus" @click="router.push({ name: 'feedback-submit' })" />
      </div>

      <!-- Filters -->
      <div class="mb-6 flex flex-wrap items-center gap-3">
        <AppSearchInput
          :model-value="search"
          placeholder="Search feedback..."
          class="flex-1"
          @update:model-value="search = $event; fetchItems()"
        />
        <Select
          v-model="statusFilter"
          :options="statuses"
          option-label="label"
          option-value="value"
          class="w-40"
          @change="fetchItems"
        />
        <Select
          v-model="sortBy"
          :options="sorts"
          option-label="label"
          option-value="value"
          class="w-44"
          @change="fetchItems"
        />
      </div>

      <AppLoadingSkeleton v-if="loading" :lines="6" />
      <template v-else>
        <FeedbackList :items="items" />
        <Paginator
          v-if="totalRecords > 15"
          :rows="15"
          :total-records="totalRecords"
          :first="(currentPage - 1) * 15"
          class="mt-6"
          @page="onPage"
        />
      </template>
    </div>
  </PublicLayout>
</template>
