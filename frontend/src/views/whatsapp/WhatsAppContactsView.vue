<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useWhatsAppStore } from '@/stores/whatsapp'
import { whatsappApi } from '@/api/whatsapp'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppOptInData, CreateOptInRequest } from '@/types/whatsapp'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const store = useWhatsAppStore()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const searchQuery = ref('')
const showAddDialog = ref(false)
const showImportDialog = ref(false)
const saving = ref(false)

const addForm = ref<CreateOptInRequest>({
  phone_number: '',
  customer_name: '',
  source: 'manual',
  tags: [],
})

const tagInput = ref('')

onMounted(() => {
  fetchContacts()
})

watch(searchQuery, () => {
  fetchContacts()
})

function fetchContacts(page = 1) {
  store.fetchContacts(workspaceId.value, {
    page,
    search: searchQuery.value || undefined,
  })
}

function resetForm() {
  addForm.value = { phone_number: '', customer_name: '', source: 'manual', tags: [] }
  tagInput.value = ''
}

async function addContact() {
  saving.value = true
  try {
    await whatsappApi.createContact(workspaceId.value, addForm.value)
    toast.success('Contact added')
    showAddDialog.value = false
    resetForm()
    fetchContacts()
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

async function deleteContact(contact: WhatsAppOptInData) {
  if (!confirm(`Remove ${contact.phone_number}?`)) return
  try {
    await whatsappApi.deleteContact(workspaceId.value, contact.id)
    toast.success('Contact removed')
    fetchContacts()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

const fileInput = ref<HTMLInputElement | null>(null)

async function importContacts() {
  const file = fileInput.value?.files?.[0]
  if (!file) return
  saving.value = true
  try {
    const result = await whatsappApi.importContacts(workspaceId.value, file)
    toast.success(`Imported ${result.imported} contacts, ${result.skipped} skipped`)
    showImportDialog.value = false
    fetchContacts()
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

function addTag() {
  const tag = tagInput.value.trim()
  if (tag && !addForm.value.tags?.includes(tag)) {
    addForm.value.tags = [...(addForm.value.tags || []), tag]
  }
  tagInput.value = ''
}

function removeTag(tag: string) {
  addForm.value.tags = (addForm.value.tags || []).filter((t) => t !== tag)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}
</script>

<template>
  <AppPageHeader title="WhatsApp Contacts" description="Manage opted-in contacts for WhatsApp messaging">
    <template #actions>
      <div class="flex gap-2">
        <button
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="showImportDialog = true"
        >
          <i class="pi pi-upload mr-1" />
          Import CSV
        </button>
        <button
          class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
          @click="showAddDialog = true"
        >
          <i class="pi pi-plus mr-1" />
          Add Contact
        </button>
      </div>
    </template>
  </AppPageHeader>

  <AppCard :padding="false">
    <!-- Search -->
    <div class="border-b border-gray-200 px-4 py-3">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search by phone or name..."
        class="w-full max-w-sm rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
      />
    </div>

    <!-- Loading -->
    <div v-if="store.loadingContacts && store.contacts.length === 0" class="flex items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
    </div>

    <!-- Empty -->
    <div v-else-if="store.contacts.length === 0" class="py-12 text-center text-gray-400">
      <i class="pi pi-users mb-2 text-3xl" />
      <p class="text-sm">No contacts found</p>
    </div>

    <!-- Table -->
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Phone</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Source</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Opted In</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tags</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="contact in store.contacts" :key="contact.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5 text-sm font-mono text-gray-900">{{ contact.phone_number }}</td>
          <td class="px-4 py-2.5 text-sm text-gray-700">{{ contact.customer_name || '-' }}</td>
          <td class="px-4 py-2.5">
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs capitalize text-gray-600">{{ contact.source }}</span>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-500">{{ formatDate(contact.opted_in_at) }}</td>
          <td class="px-4 py-2.5">
            <span
              class="rounded-full px-2 py-0.5 text-xs font-medium"
              :class="contact.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            >
              {{ contact.is_active ? 'Active' : 'Opted Out' }}
            </span>
          </td>
          <td class="px-4 py-2.5">
            <div class="flex flex-wrap gap-1">
              <span v-for="tag in (contact.tags || [])" :key="tag" class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-600">
                {{ tag }}
              </span>
            </div>
          </td>
          <td class="px-4 py-2.5 text-right">
            <button
              class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500"
              title="Remove"
              @click="deleteContact(contact)"
            >
              <i class="pi pi-trash text-sm" />
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <div
      v-if="store.contactsPagination && store.contactsPagination.last_page > 1"
      class="flex items-center justify-between border-t border-gray-200 px-4 py-2 text-sm text-gray-500"
    >
      <span>{{ store.contactsPagination.total }} contacts</span>
      <div class="flex gap-1">
        <button
          :disabled="store.contactsPagination.current_page <= 1"
          class="rounded px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
          @click="fetchContacts(store.contactsPagination!.current_page - 1)"
        >
          Prev
        </button>
        <button
          :disabled="store.contactsPagination.current_page >= store.contactsPagination.last_page"
          class="rounded px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
          @click="fetchContacts(store.contactsPagination!.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </AppCard>

  <!-- Add contact dialog -->
  <Teleport to="body">
    <div v-if="showAddDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAddDialog = false">
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Add Contact</h3>
        <form class="space-y-3" @submit.prevent="addContact">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number *</label>
            <input
              v-model="addForm.phone_number"
              type="tel"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
              placeholder="+91XXXXXXXXXX"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Customer Name</label>
            <input
              v-model="addForm.customer_name"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
              placeholder="John Doe"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Tags</label>
            <div class="flex gap-1 flex-wrap mb-1">
              <span v-for="tag in addForm.tags" :key="tag" class="flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-600">
                {{ tag }}
                <button type="button" class="hover:text-red-500" @click="removeTag(tag)">&times;</button>
              </span>
            </div>
            <div class="flex gap-1">
              <input
                v-model="tagInput"
                type="text"
                class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                placeholder="Add tag..."
                @keydown.enter.prevent="addTag"
              />
              <button type="button" class="rounded-lg border border-gray-300 px-2 py-1 text-sm hover:bg-gray-50" @click="addTag">Add</button>
            </div>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showAddDialog = false">Cancel</button>
            <button type="submit" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
              <i v-if="saving" class="pi pi-spin pi-spinner mr-1" />
              Add Contact
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Import dialog -->
  <Teleport to="body">
    <div v-if="showImportDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showImportDialog = false">
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Import Contacts</h3>
        <p class="mb-3 text-sm text-gray-600">
          Upload a CSV file with columns: <code class="rounded bg-gray-100 px-1 text-xs">phone_number</code>,
          <code class="rounded bg-gray-100 px-1 text-xs">customer_name</code> (optional),
          <code class="rounded bg-gray-100 px-1 text-xs">tags</code> (optional, comma-separated).
        </p>
        <input ref="fileInput" type="file" accept=".csv,.xlsx,.txt" class="w-full text-sm" />
        <div class="mt-4 flex justify-end gap-2">
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showImportDialog = false">Cancel</button>
          <button :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50" @click="importContacts">
            <i v-if="saving" class="pi pi-spin pi-spinner mr-1" />
            Import
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
