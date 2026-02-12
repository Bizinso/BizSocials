<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useWhatsAppStore } from '@/stores/whatsapp'
import { whatsappApi } from '@/api/whatsapp'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppConversationData, SendWhatsAppMessageRequest } from '@/types/whatsapp'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import WhatsAppConversationList from '@/components/whatsapp/WhatsAppConversationList.vue'
import WhatsAppMessageThread from '@/components/whatsapp/WhatsAppMessageThread.vue'
import WhatsAppContactCard from '@/components/whatsapp/WhatsAppContactCard.vue'

const route = useRoute()
const store = useWhatsAppStore()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

// Filters
const filterStatus = ref('')
const filterSearch = ref('')
const showContactPanel = ref(true)

onMounted(() => {
  fetchConversations()
})

watch([filterStatus, filterSearch], () => {
  fetchConversations()
})

function fetchConversations(page = 1) {
  store.fetchConversations(workspaceId.value, {
    page,
    status: filterStatus.value || undefined,
    search: filterSearch.value || undefined,
  })
}

function onSelectConversation(conversation: WhatsAppConversationData) {
  store.setCurrentConversation(conversation)
  store.fetchMessages(workspaceId.value, conversation.id)
}

async function onSendMessage(data: SendWhatsAppMessageRequest) {
  if (!store.currentConversation) return
  try {
    await store.sendMessage(workspaceId.value, store.currentConversation.id, data)
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function onSendMedia(data: SendWhatsAppMessageRequest) {
  if (!store.currentConversation) return
  try {
    await whatsappApi.sendMedia(workspaceId.value, store.currentConversation.id, data)
    // Refresh messages
    store.fetchMessages(workspaceId.value, store.currentConversation.id)
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function onLoadMore() {
  if (!store.currentConversation || !store.messagesPagination) return
  store.fetchMessages(workspaceId.value, store.currentConversation.id, {
    page: store.messagesPagination.current_page + 1,
  })
}

async function onAssign(userId: string | null, team: string | null) {
  if (!store.currentConversation) return
  try {
    await store.assignConversation(workspaceId.value, store.currentConversation.id, {
      user_id: userId,
      team,
    })
    toast.success('Conversation assigned')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function onResolve() {
  if (!store.currentConversation) return
  try {
    await store.resolveConversation(workspaceId.value, store.currentConversation.id)
    toast.success('Conversation resolved')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}
</script>

<template>
  <AppPageHeader title="WhatsApp Inbox" description="Manage customer conversations">
    <template #actions>
      <div class="flex items-center gap-2">
        <button
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="showContactPanel = !showContactPanel"
        >
          <i class="pi pi-user mr-1" />
          {{ showContactPanel ? 'Hide' : 'Show' }} Info
        </button>
      </div>
    </template>
  </AppPageHeader>

  <div class="flex h-[calc(100vh-12rem)] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <!-- Left: Conversation list -->
    <div class="w-80 shrink-0 border-r border-gray-200">
      <div class="border-b border-gray-200 p-3">
        <input
          v-model="filterSearch"
          type="text"
          placeholder="Search conversations..."
          class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
        />
        <div class="mt-2 flex gap-1">
          <button
            v-for="status in ['', 'active', 'pending', 'resolved']"
            :key="status"
            class="rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors"
            :class="filterStatus === status ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:bg-gray-100'"
            @click="filterStatus = status"
          >
            {{ status || 'All' }}
          </button>
        </div>
      </div>
      <WhatsAppConversationList
        :conversations="store.conversations"
        :loading="store.loadingConversations"
        :pagination="store.conversationsPagination"
        :active-id="store.currentConversation?.id"
        @select="onSelectConversation"
        @page="fetchConversations"
      />
    </div>

    <!-- Center: Message thread -->
    <div class="flex flex-1 flex-col">
      <template v-if="store.currentConversation">
        <WhatsAppMessageThread
          :conversation="store.currentConversation"
          :messages="store.messages"
          :loading="store.loadingMessages"
          :sending="store.sendingMessage"
          :pagination="store.messagesPagination"
          @send="onSendMessage"
          @send-media="onSendMedia"
          @load-more="onLoadMore"
        />
      </template>
      <div v-else class="flex flex-1 items-center justify-center text-gray-400">
        <div class="text-center">
          <i class="pi pi-comments mb-2 text-4xl" />
          <p class="text-sm">Select a conversation to start messaging</p>
        </div>
      </div>
    </div>

    <!-- Right: Contact info panel -->
    <div
      v-if="showContactPanel && store.currentConversation"
      class="w-72 shrink-0 overflow-y-auto border-l border-gray-200"
    >
      <div class="border-b border-gray-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-gray-900">Contact Info</h3>
      </div>
      <WhatsAppContactCard :conversation="store.currentConversation" />

      <!-- Actions -->
      <div class="border-t border-gray-200 p-4">
        <div class="space-y-2">
          <button
            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            @click="onResolve"
          >
            <i class="pi pi-check mr-1" />
            Resolve
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
