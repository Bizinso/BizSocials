<script setup lang="ts">
import { ref, computed } from 'vue'
import type { WhatsAppCampaignAudienceFilter } from '@/types/whatsapp-marketing'

const props = defineProps<{
  modelValue: WhatsAppCampaignAudienceFilter
}>()

const emit = defineEmits<{
  'update:modelValue': [filter: WhatsAppCampaignAudienceFilter]
}>()

const tagInput = ref('')
const excludeTagInput = ref('')

const filter = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

function addTag() {
  const tag = tagInput.value.trim()
  if (tag && !(filter.value.tags || []).includes(tag)) {
    filter.value = { ...filter.value, tags: [...(filter.value.tags || []), tag] }
  }
  tagInput.value = ''
}

function removeTag(tag: string) {
  filter.value = { ...filter.value, tags: (filter.value.tags || []).filter((t) => t !== tag) }
}

function addExcludeTag() {
  const tag = excludeTagInput.value.trim()
  if (tag && !(filter.value.exclude_tags || []).includes(tag)) {
    filter.value = { ...filter.value, exclude_tags: [...(filter.value.exclude_tags || []), tag] }
  }
  excludeTagInput.value = ''
}

function removeExcludeTag(tag: string) {
  filter.value = { ...filter.value, exclude_tags: (filter.value.exclude_tags || []).filter((t) => t !== tag) }
}

function updateOptInAfter(date: string) {
  filter.value = { ...filter.value, opt_in_after: date || undefined }
}
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-sm font-semibold text-gray-900">Audience Filter</h3>

    <!-- Include tags -->
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Include Tags</label>
      <p class="mb-1 text-xs text-gray-400">Only contacts with at least one of these tags</p>
      <div class="mb-1 flex flex-wrap gap-1">
        <span v-for="tag in (filter.tags || [])" :key="tag" class="flex items-center gap-1 rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">
          {{ tag }}
          <button type="button" class="hover:text-red-500" @click="removeTag(tag)">&times;</button>
        </span>
      </div>
      <div class="flex gap-1">
        <input
          v-model="tagInput"
          type="text"
          class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm"
          placeholder="Add tag..."
          @keydown.enter.prevent="addTag"
        />
        <button type="button" class="rounded-lg border border-gray-300 px-2 py-1 text-sm hover:bg-gray-50" @click="addTag">Add</button>
      </div>
    </div>

    <!-- Exclude tags -->
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Exclude Tags</label>
      <div class="mb-1 flex flex-wrap gap-1">
        <span v-for="tag in (filter.exclude_tags || [])" :key="tag" class="flex items-center gap-1 rounded bg-red-50 px-2 py-0.5 text-xs text-red-700">
          {{ tag }}
          <button type="button" class="hover:text-red-500" @click="removeExcludeTag(tag)">&times;</button>
        </span>
      </div>
      <div class="flex gap-1">
        <input
          v-model="excludeTagInput"
          type="text"
          class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm"
          placeholder="Exclude tag..."
          @keydown.enter.prevent="addExcludeTag"
        />
        <button type="button" class="rounded-lg border border-gray-300 px-2 py-1 text-sm hover:bg-gray-50" @click="addExcludeTag">Add</button>
      </div>
    </div>

    <!-- Opt-in date -->
    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700">Opted In After</label>
      <input
        type="date"
        :value="filter.opt_in_after"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        @input="updateOptInAfter(($event.target as HTMLInputElement).value)"
      />
    </div>
  </div>
</template>
