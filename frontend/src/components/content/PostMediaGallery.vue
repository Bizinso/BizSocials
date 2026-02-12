<script setup lang="ts">
import { computed } from 'vue'
import type { PostMediaData } from '@/types/content'
import Button from 'primevue/button'
import Image from 'primevue/image'

const props = defineProps<{
  media: PostMediaData[]
  editable?: boolean
}>()

const emit = defineEmits<{
  remove: [media: PostMediaData]
  reorder: [order: Record<string, number>]
}>()

const sortedMedia = computed(() =>
  [...props.media].sort((a, b) => a.sort_order - b.sort_order),
)

function removeItem(item: PostMediaData) {
  emit('remove', item)
}
</script>

<template>
  <div v-if="sortedMedia.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
    <div
      v-for="item in sortedMedia"
      :key="item.id"
      class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
    >
      <Image
        v-if="item.media_type === 'image'"
        :src="item.file_url || item.file_path"
        :alt="item.original_filename || 'Media'"
        preview
        image-class="h-32 w-full object-cover"
      />
      <div
        v-else
        class="flex h-32 items-center justify-center"
      >
        <i class="pi pi-video text-3xl text-gray-400" />
      </div>

      <div class="p-1.5">
        <p class="truncate text-xs text-gray-500">{{ item.original_filename || 'Untitled' }}</p>
      </div>

      <Button
        v-if="editable"
        icon="pi pi-times"
        severity="danger"
        rounded
        text
        size="small"
        class="absolute right-1 top-1 !h-6 !w-6 opacity-0 group-hover:opacity-100"
        @click="removeItem(item)"
      />
    </div>
  </div>
</template>
