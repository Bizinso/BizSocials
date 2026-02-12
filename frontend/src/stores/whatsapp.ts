import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { whatsappApi } from '@/api/whatsapp'
import type {
  WhatsAppBusinessAccountData,
  WhatsAppConversationData,
  WhatsAppMessageData,
  WhatsAppOptInData,
  SendWhatsAppMessageRequest,
  AssignConversationRequest,
} from '@/types/whatsapp'
import type { PaginationMeta } from '@/types/api'

export const useWhatsAppStore = defineStore('whatsapp', () => {
  // ─── State ────────────────────────────────────────────
  const accounts = ref<WhatsAppBusinessAccountData[]>([])
  const conversations = ref<WhatsAppConversationData[]>([])
  const currentConversation = ref<WhatsAppConversationData | null>(null)
  const messages = ref<WhatsAppMessageData[]>([])
  const contacts = ref<WhatsAppOptInData[]>([])

  const conversationsPagination = ref<PaginationMeta | null>(null)
  const messagesPagination = ref<PaginationMeta | null>(null)
  const contactsPagination = ref<PaginationMeta | null>(null)

  const loadingAccounts = ref(false)
  const loadingConversations = ref(false)
  const loadingMessages = ref(false)
  const loadingContacts = ref(false)
  const sendingMessage = ref(false)

  // ─── Computed ─────────────────────────────────────────
  const hasAccounts = computed(() => accounts.value.length > 0)
  const unreadCount = computed(() =>
    conversations.value.filter((c) => c.status === 'active' && c.message_count > 0).length,
  )

  // ─── Actions ──────────────────────────────────────────
  async function fetchAccounts() {
    loadingAccounts.value = true
    try {
      accounts.value = await whatsappApi.getAccounts()
    } finally {
      loadingAccounts.value = false
    }
  }

  async function fetchConversations(
    workspaceId: string,
    params?: { page?: number; per_page?: number; status?: string; search?: string },
  ) {
    loadingConversations.value = true
    try {
      const response = await whatsappApi.getConversations(workspaceId, params)
      conversations.value = response.data
      conversationsPagination.value = response.meta
    } finally {
      loadingConversations.value = false
    }
  }

  async function fetchMessages(workspaceId: string, conversationId: string, params?: { page?: number; per_page?: number }) {
    loadingMessages.value = true
    try {
      const response = await whatsappApi.getMessages(workspaceId, conversationId, params)
      messages.value = response.data
      messagesPagination.value = response.meta
    } finally {
      loadingMessages.value = false
    }
  }

  async function sendMessage(workspaceId: string, conversationId: string, data: SendWhatsAppMessageRequest) {
    sendingMessage.value = true
    try {
      const message = await whatsappApi.sendMessage(workspaceId, conversationId, data)
      messages.value.unshift(message)
      return message
    } finally {
      sendingMessage.value = false
    }
  }

  async function assignConversation(workspaceId: string, conversationId: string, data: AssignConversationRequest) {
    await whatsappApi.assignConversation(workspaceId, conversationId, data)
    // Refresh the conversation
    const updated = await whatsappApi.getConversation(workspaceId, conversationId)
    const index = conversations.value.findIndex((c) => c.id === conversationId)
    if (index !== -1) {
      conversations.value[index] = updated
    }
    if (currentConversation.value?.id === conversationId) {
      currentConversation.value = updated
    }
  }

  async function resolveConversation(workspaceId: string, conversationId: string) {
    await whatsappApi.resolveConversation(workspaceId, conversationId)
    const index = conversations.value.findIndex((c) => c.id === conversationId)
    if (index !== -1) {
      conversations.value[index] = { ...conversations.value[index], status: 'resolved' as never }
    }
  }

  async function fetchContacts(workspaceId: string, params?: { page?: number; per_page?: number; search?: string }) {
    loadingContacts.value = true
    try {
      const response = await whatsappApi.getContacts(workspaceId, params)
      contacts.value = response.data
      contactsPagination.value = response.meta
    } finally {
      loadingContacts.value = false
    }
  }

  function setCurrentConversation(conversation: WhatsAppConversationData | null) {
    currentConversation.value = conversation
  }

  function clear() {
    accounts.value = []
    conversations.value = []
    currentConversation.value = null
    messages.value = []
    contacts.value = []
    conversationsPagination.value = null
    messagesPagination.value = null
    contactsPagination.value = null
  }

  return {
    accounts,
    conversations,
    currentConversation,
    messages,
    contacts,
    conversationsPagination,
    messagesPagination,
    contactsPagination,
    loadingAccounts,
    loadingConversations,
    loadingMessages,
    loadingContacts,
    sendingMessage,
    hasAccounts,
    unreadCount,
    fetchAccounts,
    fetchConversations,
    fetchMessages,
    sendMessage,
    assignConversation,
    resolveConversation,
    fetchContacts,
    setCurrentConversation,
    clear,
  }
})
