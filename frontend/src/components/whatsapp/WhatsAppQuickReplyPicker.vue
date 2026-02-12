<script setup lang="ts">
import { ref, computed } from 'vue'

export interface QuickReply {
  id: string
  title: string
  content: string
  shortcut?: string
  category?: string
}

const props = defineProps<{
  replies: QuickReply[]
}>()

const emit = defineEmits<{
  select: [content: string]
}>()

const searchQuery = ref('')
const visible = ref(false)

const filtered = computed(() => {
  if (!searchQuery.value) return props.replies
  const q = searchQuery.value.toLowerCase()
  return props.replies.filter(
    (r) =>
      r.title.toLowerCase().includes(q) ||
      r.content.toLowerCase().includes(q) ||
      (r.shortcut && r.shortcut.toLowerCase().includes(q)),
  )
})

function select(reply: QuickReply) {
  emit('select', reply.content)
  visible.value = false
  searchQuery.value = ''
}

function toggle() {
  visible.value = !visible.value
  if (!visible.value) searchQuery.value = ''
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
      title="Quick replies"
      @click="toggle"
    >
      <i class="pi pi-bolt" />
    </button>

    <div
      v-if="visible"
      class="absolute bottom-full left-0 z-10 mb-2 w-72 rounded-lg border border-gray-200 bg-white shadow-lg"
    >
      <div class="border-b border-gray-100 p-2">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search replies..."
          class="w-full rounded-md border border-gray-200 px-2 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>
      <ul class="max-h-48 overflow-y-auto">
        <li
          v-for="reply in filtered"
          :key="reply.id"
          class="cursor-pointer border-b border-gray-50 px-3 py-2 hover:bg-gray-50"
          @click="select(reply)"
        >
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-800">{{ reply.title }}</span>
            <span v-if="reply.shortcut" class="rounded bg-gray-100 px-1 text-xs text-gray-500">{{ reply.shortcut }}</span>
          </div>
          <p class="mt-0.5 truncate text-xs text-gray-500">{{ reply.content }}</p>
        </li>
        <li v-if="filtered.length === 0" class="px-3 py-4 text-center text-sm text-gray-400">
          No replies found
        </li>
      </ul>
    </div>
  </div>
</template>
