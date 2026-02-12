<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'
import { adminKBApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/formatters'
import type { KBArticleData } from '@/types/kb'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'

const toast = useToast()
const { confirmDelete } = useConfirm()
const articles = ref<KBArticleData[]>([])
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)
const search = ref('')

async function fetchArticles() {
  loading.value = true
  try {
    const result = await adminKBApi.listArticles({ page: currentPage.value, search: search.value || undefined })
    articles.value = result.data
    totalRecords.value = result.meta.total
  } finally {
    loading.value = false
  }
}

onMounted(fetchArticles)

async function publishArticle(article: KBArticleData) {
  try {
    await adminKBApi.publishArticle(article.id)
    toast.success('Article published')
    fetchArticles()
  } catch { toast.error('Failed to publish') }
}

async function deleteArticle(article: KBArticleData) {
  confirmDelete({
    message: `Delete "${article.title}"?`,
    async onAccept() {
      try {
        await adminKBApi.deleteArticle(article.id)
        toast.success('Article deleted')
        fetchArticles()
      } catch { toast.error('Failed to delete') }
    },
  })
}

function statusSeverity(status: string) {
  if (status === 'published') return 'success'
  if (status === 'draft') return 'warn'
  return 'secondary'
}

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchArticles()
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">KB Articles</h1>

    <div class="mb-4">
      <AppSearchInput :model-value="search" placeholder="Search articles..." @update:model-value="search = $event; fetchArticles()" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else>
      <DataTable :value="articles" striped-rows>
        <Column field="title" header="Title">
          <template #body="{ data }">
            <span class="font-medium text-gray-900">{{ data.title }}</span>
          </template>
        </Column>
        <Column field="category_name" header="Category" style="width: 140px" />
        <Column field="status" header="Status" style="width: 100px">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
          </template>
        </Column>
        <Column field="view_count" header="Views" style="width: 80px" />
        <Column field="created_at" header="Created" style="width: 120px">
          <template #body="{ data }">
            <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button v-if="data.status === 'draft'" icon="pi pi-check" severity="success" text size="small" title="Publish" @click="publishArticle(data)" />
              <Button icon="pi pi-trash" severity="danger" text size="small" @click="deleteArticle(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
      <Paginator v-if="totalRecords > 15" :rows="15" :total-records="totalRecords" :first="(currentPage - 1) * 15" class="mt-4" @page="onPage" />
    </template>
  </div>
</template>
