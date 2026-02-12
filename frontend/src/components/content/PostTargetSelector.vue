<script setup lang="ts">
import { computed } from 'vue'
import { useSocialStore } from '@/stores/social'
import type { SocialAccountData } from '@/types/social'
import { getPlatformLabel, getPlatformColor } from '@/utils/platform-config'
import Checkbox from 'primevue/checkbox'

const props = defineProps<{
  modelValue: string[]
}>()

const emit = defineEmits<{
  'update:modelValue': [ids: string[]]
}>()

const socialStore = useSocialStore()

const availableAccounts = computed(() => socialStore.connectedAccounts)

function isSelected(id: string): boolean {
  return props.modelValue.includes(id)
}

function toggle(account: SocialAccountData) {
  const ids = [...props.modelValue]
  const index = ids.indexOf(account.id)
  if (index >= 0) {
    ids.splice(index, 1)
  } else {
    ids.push(account.id)
  }
  emit('update:modelValue', ids)
}

function selectAll() {
  emit(
    'update:modelValue',
    availableAccounts.value.map((a) => a.id),
  )
}

function clearAll() {
  emit('update:modelValue', [])
}
</script>

<template>
  <div>
    <div class="mb-2 flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Publish to</label>
      <div class="flex gap-2">
        <button class="text-xs text-primary-600 hover:underline" @click="selectAll">Select all</button>
        <button class="text-xs text-gray-500 hover:underline" @click="clearAll">Clear</button>
      </div>
    </div>

    <div v-if="availableAccounts.length === 0" class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
      No connected social accounts. Connect an account first.
    </div>

    <div v-else class="space-y-2">
      <label
        v-for="account in availableAccounts"
        :key="account.id"
        class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50"
        :class="{ 'border-primary-300 bg-primary-50': isSelected(account.id) }"
      >
        <Checkbox
          :model-value="isSelected(account.id)"
          :binary="true"
          @update:model-value="toggle(account)"
        />
        <i
          :class="account.platform === 'linkedin' ? 'pi pi-linkedin' : account.platform === 'facebook' ? 'pi pi-facebook' : account.platform === 'instagram' ? 'pi pi-instagram' : 'pi pi-twitter'"
          :style="{ color: getPlatformColor(account.platform) }"
        />
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900">{{ account.account_name }}</p>
          <p class="text-xs text-gray-500">{{ getPlatformLabel(account.platform) }}</p>
        </div>
      </label>
    </div>
  </div>
</template>
