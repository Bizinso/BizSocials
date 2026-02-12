import { ref, computed } from 'vue'
import type { PaginationMeta } from '@/types/api'

export function usePagination() {
  const meta = ref<PaginationMeta | null>(null)
  const currentPage = ref(1)

  const hasPages = computed(() => meta.value !== null && meta.value.last_page > 1)
  const totalPages = computed(() => meta.value?.last_page ?? 1)
  const totalItems = computed(() => meta.value?.total ?? 0)
  const hasNext = computed(() => meta.value !== null && meta.value.current_page < meta.value.last_page)
  const hasPrev = computed(() => meta.value !== null && meta.value.current_page > 1)

  function setMeta(newMeta: PaginationMeta) {
    meta.value = newMeta
    currentPage.value = newMeta.current_page
  }

  function goToPage(page: number) {
    currentPage.value = page
  }

  function nextPage() {
    if (hasNext.value) {
      currentPage.value++
    }
  }

  function prevPage() {
    if (hasPrev.value) {
      currentPage.value--
    }
  }

  return {
    meta,
    currentPage,
    hasPages,
    totalPages,
    totalItems,
    hasNext,
    hasPrev,
    setMeta,
    goToPage,
    nextPage,
    prevPage,
  }
}
