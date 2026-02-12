<script setup lang="ts">
import type { InboxItemData } from '@/types/inbox'
import AppAvatar from '@/components/shared/AppAvatar.vue'
import AppPlatformIcon from '@/components/shared/AppPlatformIcon.vue'
import Tag from 'primevue/tag'
import Checkbox from 'primevue/checkbox'
import { formatRelative, truncate } from '@/utils/formatters'

const props = defineProps<{
  item: InboxItemData
  selected: boolean
}>()

const emit = defineEmits<{
  click: [item: InboxItemData]
  select: [item: InboxItemData]
}>()

function statusSeverity(status: string) {
  switch (status) {
    case 'unread': return 'info'
    case 'read': return 'secondary'
    case 'resolved': return 'success'
    case 'archived': return 'warn'
    default: return 'secondary'
  }
}
</script>

<template>
  <div
    class="flex cursor-pointer items-start gap-3 border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50"
    :class="{ 'bg-blue-50/50': item.status === 'unread', 'bg-primary-50/30': selected }"
    @click="emit('click', item)"
  >
    <Checkbox
      :model-value="selected"
      :binary="true"
      class="mt-1"
      @click.stop
      @update:model-value="emit('select', item)"
    />

    <AppAvatar :name="item.author_name" :src="item.author_avatar_url" size="sm" />

    <div class="min-w-0 flex-1">
      <div class="mb-0.5 flex items-center gap-2">
        <span class="text-sm font-medium text-gray-900" :class="{ 'font-bold': item.status === 'unread' }">
          {{ item.author_name }}
        </span>
        <span v-if="item.author_username" class="text-xs text-gray-400">@{{ item.author_username }}</span>
        <AppPlatformIcon v-if="item.platform" :platform="item.platform" size="sm" />
        <Tag :value="item.item_type" :severity="item.item_type === 'comment' ? 'info' : 'warn'" class="!text-[10px]" />
      </div>
      <p class="text-sm text-gray-700" :class="{ 'font-medium': item.status === 'unread' }">
        {{ truncate(item.content_text, 120) }}
      </p>
      <div class="mt-1 flex items-center gap-3 text-xs text-gray-400">
        <span>{{ formatRelative(item.platform_created_at) }}</span>
        <span v-if="item.account_name">via {{ item.account_name }}</span>
        <span v-if="item.assigned_to_name" class="text-primary-600">
          <i class="pi pi-user mr-0.5" />{{ item.assigned_to_name }}
        </span>
        <span v-if="item.reply_count > 0">
          <i class="pi pi-reply mr-0.5" />{{ item.reply_count }}
        </span>
      </div>
    </div>

    <Tag :value="item.status" :severity="statusSeverity(item.status)" class="shrink-0 !text-[10px]" />
  </div>
</template>
