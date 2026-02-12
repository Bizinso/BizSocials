<script setup lang="ts">
import { computed } from 'vue'
import type { WhatsAppConversationData } from '@/types/whatsapp'
import WhatsAppServiceWindowBadge from './WhatsAppServiceWindowBadge.vue'

const props = defineProps<{
  conversation: WhatsAppConversationData
}>()

const customerDisplay = computed(() => props.conversation.customer_name || props.conversation.customer_profile_name || props.conversation.customer_phone)

const assigneeDisplay = computed(() => {
  if (props.conversation.assigned_to_name) return props.conversation.assigned_to_name
  if (props.conversation.assigned_to_team) return props.conversation.assigned_to_team
  return 'Unassigned'
})
</script>

<template>
  <div class="space-y-4 p-4">
    <!-- Customer info -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Customer</h3>
      <p class="mt-1 text-sm text-gray-700">{{ customerDisplay }}</p>
      <p class="text-xs text-gray-500">{{ conversation.customer_phone }}</p>
      <p v-if="conversation.customer_profile_name && conversation.customer_name" class="text-xs text-gray-400">
        WA: {{ conversation.customer_profile_name }}
      </p>
    </div>

    <!-- Service window -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Service Window</h3>
      <div class="mt-1">
        <WhatsAppServiceWindowBadge
          :last-customer-message-at="conversation.last_customer_message_at"
          :is-within-service-window="conversation.is_within_service_window"
        />
      </div>
    </div>

    <!-- Assignment -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Assigned to</h3>
      <p class="mt-1 text-sm text-gray-700">{{ assigneeDisplay }}</p>
    </div>

    <!-- Stats -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Stats</h3>
      <dl class="mt-1 space-y-1 text-sm">
        <div class="flex justify-between">
          <dt class="text-gray-500">Messages</dt>
          <dd class="font-medium text-gray-700">{{ conversation.message_count }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-gray-500">Status</dt>
          <dd class="font-medium capitalize text-gray-700">{{ conversation.status }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-gray-500">Priority</dt>
          <dd class="font-medium capitalize text-gray-700">{{ conversation.priority }}</dd>
        </div>
      </dl>
    </div>

    <!-- Phone number -->
    <div v-if="conversation.phone_display_name">
      <h3 class="text-sm font-semibold text-gray-900">Business Number</h3>
      <p class="mt-1 text-sm text-gray-700">{{ conversation.phone_display_name }}</p>
      <p v-if="conversation.phone_number" class="text-xs text-gray-500">{{ conversation.phone_number }}</p>
    </div>
  </div>
</template>
