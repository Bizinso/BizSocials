<script setup lang="ts">
import { computed } from 'vue'
import type { WhatsAppTemplateData } from '@/types/whatsapp-marketing'

const props = defineProps<{
  template: WhatsAppTemplateData | null
  headerType?: string
  headerContent?: string
  bodyText?: string
  footerText?: string
  buttons?: Array<{ type: string; text: string }>
}>()

const header = computed(() => props.template?.header_content || props.headerContent)
const body = computed(() => props.template?.body_text || props.bodyText || '')
const footer = computed(() => props.template?.footer_text || props.footerText)
const btns = computed(() => props.template?.buttons || props.buttons || [])
const hdrType = computed(() => props.template?.header_type || props.headerType || 'none')
</script>

<template>
  <div class="mx-auto w-72">
    <!-- Phone frame -->
    <div class="overflow-hidden rounded-2xl border-4 border-gray-800 bg-[#ECE5DD]">
      <!-- Status bar -->
      <div class="bg-[#075E54] px-3 py-2 text-xs text-white">
        <div class="flex items-center gap-2">
          <i class="pi pi-arrow-left text-xs" />
          <div class="h-6 w-6 rounded-full bg-gray-400" />
          <span class="text-sm font-medium">Preview</span>
        </div>
      </div>

      <!-- Message area -->
      <div class="min-h-[300px] p-3">
        <div class="max-w-[85%] rounded-lg bg-white p-2 shadow-sm">
          <!-- Header -->
          <div v-if="hdrType !== 'none' && header" class="mb-1">
            <div
              v-if="hdrType === 'image'"
              class="mb-1 flex h-32 items-center justify-center rounded bg-gray-100"
            >
              <i class="pi pi-image text-2xl text-gray-400" />
            </div>
            <div
              v-else-if="hdrType === 'video'"
              class="mb-1 flex h-32 items-center justify-center rounded bg-gray-100"
            >
              <i class="pi pi-play text-2xl text-gray-400" />
            </div>
            <div
              v-else-if="hdrType === 'document'"
              class="mb-1 flex items-center gap-1 rounded bg-gray-100 p-2 text-xs text-gray-500"
            >
              <i class="pi pi-file" />
              <span>Document</span>
            </div>
            <p v-else class="text-sm font-semibold text-gray-900">{{ header }}</p>
          </div>

          <!-- Body -->
          <p class="whitespace-pre-wrap text-sm text-gray-800">{{ body }}</p>

          <!-- Footer -->
          <p v-if="footer" class="mt-1 text-xs text-gray-400">{{ footer }}</p>

          <!-- Time -->
          <div class="mt-0.5 text-right text-[10px] text-gray-400">12:00</div>
        </div>

        <!-- Buttons -->
        <div v-if="btns.length > 0" class="mt-1 max-w-[85%] space-y-1">
          <button
            v-for="(btn, i) in btns"
            :key="i"
            class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-xs font-medium text-blue-500"
          >
            {{ btn.text }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
