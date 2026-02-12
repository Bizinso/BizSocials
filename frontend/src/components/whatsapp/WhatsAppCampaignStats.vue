<script setup lang="ts">
import { computed } from 'vue'
import type { WhatsAppCampaignData } from '@/types/whatsapp-marketing'

const props = defineProps<{
  campaign: WhatsAppCampaignData
}>()

const bars = computed(() => [
  { label: 'Sent', value: props.campaign.sent_count, color: 'bg-blue-500', pct: props.campaign.total_recipients > 0 ? (props.campaign.sent_count / props.campaign.total_recipients) * 100 : 0 },
  { label: 'Delivered', value: props.campaign.delivered_count, color: 'bg-green-500', pct: props.campaign.sent_count > 0 ? (props.campaign.delivered_count / props.campaign.sent_count) * 100 : 0 },
  { label: 'Read', value: props.campaign.read_count, color: 'bg-indigo-500', pct: props.campaign.delivered_count > 0 ? (props.campaign.read_count / props.campaign.delivered_count) * 100 : 0 },
  { label: 'Failed', value: props.campaign.failed_count, color: 'bg-red-500', pct: props.campaign.total_recipients > 0 ? (props.campaign.failed_count / props.campaign.total_recipients) * 100 : 0 },
])
</script>

<template>
  <div class="space-y-3">
    <div class="grid grid-cols-4 gap-3 text-center">
      <div v-for="bar in bars" :key="bar.label" class="rounded-lg border border-gray-200 p-2">
        <p class="text-lg font-semibold text-gray-900">{{ bar.value }}</p>
        <p class="text-xs text-gray-500">{{ bar.label }}</p>
      </div>
    </div>

    <div class="space-y-2">
      <div v-for="bar in bars" :key="bar.label" class="flex items-center gap-2">
        <span class="w-20 text-xs text-gray-600">{{ bar.label }}</span>
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
          <div :class="bar.color" class="h-full rounded-full transition-all" :style="{ width: `${bar.pct}%` }" />
        </div>
        <span class="w-12 text-right text-xs text-gray-500">{{ bar.pct.toFixed(1) }}%</span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
      <div class="text-center">
        <p class="text-sm font-semibold text-gray-900">{{ campaign.delivery_rate }}%</p>
        <p class="text-xs text-gray-500">Delivery Rate</p>
      </div>
      <div class="text-center">
        <p class="text-sm font-semibold text-gray-900">{{ campaign.read_rate }}%</p>
        <p class="text-xs text-gray-500">Read Rate</p>
      </div>
    </div>
  </div>
</template>
