<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { inboxContactApi } from '@/api/inbox-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxContactData, CreateInboxContactRequest } from '@/types/inbox-extended'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const contacts = ref<InboxContactData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const filterPlatform = ref('')
const filterSearch = ref('')
const selectedContact = ref<InboxContactData | null>(null)

const form = ref({
  platform: '',
  platform_user_id: '',
  display_name: '',
  username: '',
  email: '',
  phone: '',
  notes: '',
})

const platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok']

const platformColors: Record<string, string> = {
  facebook: 'bg-blue-50 text-blue-700',
  instagram: 'bg-pink-50 text-pink-700',
  twitter: 'bg-sky-50 text-sky-700',
  linkedin: 'bg-indigo-50 text-indigo-700',
  youtube: 'bg-red-50 text-red-700',
  tiktok: 'bg-gray-100 text-gray-800',
}

onMounted(() => fetchContacts())

async function fetchContacts(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 20 }
    if (filterPlatform.value) params.platform = filterPlatform.value
    if (filterSearch.value) params.search = filterSearch.value
    const res = await inboxContactApi.list(workspaceId.value, params)
    contacts.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

function resetForm() {
  form.value = { platform: '', platform_user_id: '', display_name: '', username: '', email: '', phone: '', notes: '' }
}

async function createContact() {
  saving.value = true
  try {
    const data: CreateInboxContactRequest = {
      platform: form.value.platform,
      platform_user_id: form.value.platform_user_id,
      display_name: form.value.display_name,
    }
    if (form.value.username) data.username = form.value.username
    if (form.value.email) data.email = form.value.email
    if (form.value.phone) data.phone = form.value.phone
    if (form.value.notes) data.notes = form.value.notes
    await inboxContactApi.create(workspaceId.value, data)
    toast.success('Contact created')
    showCreate.value = false
    resetForm()
    fetchContacts()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function deleteContact(contact: InboxContactData) {
  if (!confirm(`Delete "${contact.display_name}"?`)) return
  try {
    await inboxContactApi.delete(workspaceId.value, contact.id)
    toast.success('Deleted')
    if (selectedContact.value?.id === contact.id) selectedContact.value = null
    fetchContacts()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString()
}
</script>

<template>
  <AppPageHeader title="Social CRM" description="Track contacts and interactions from your social platforms">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate; resetForm()"><i class="pi pi-plus mr-1" /> Add Contact</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createContact">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Platform *</label>
          <select v-model="form.platform" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="" disabled>Select platform</option>
            <option v-for="p in platforms" :key="p" :value="p" class="capitalize">{{ p }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Platform User ID *</label>
          <input v-model="form.platform_user_id" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Display Name *</label>
          <input v-model="form.display_name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Username</label>
          <input v-model="form.username" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="@username" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
          <input v-model="form.phone" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Notes</label>
        <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <div class="flex gap-4">
    <!-- Contact List -->
    <div class="flex-1">
      <!-- Filters -->
      <div class="mb-4 flex items-center gap-3">
        <input v-model="filterSearch" type="text" class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm" placeholder="Search contacts..." @input="fetchContacts(1)" />
        <select v-model="filterPlatform" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm" @change="fetchContacts(1)">
          <option value="">All Platforms</option>
          <option v-for="p in platforms" :key="p" :value="p" class="capitalize">{{ p }}</option>
        </select>
      </div>

      <AppCard :padding="false">
        <div v-if="loading && contacts.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
        <div v-else-if="contacts.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-users mb-2 text-3xl" /><p class="text-sm">No contacts found</p></div>
        <table v-else class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Contact</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Platform</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Interactions</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Last Seen</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="contact in contacts" :key="contact.id" class="cursor-pointer border-b border-gray-100 hover:bg-gray-50" @click="selectedContact = contact">
              <td class="px-4 py-2.5">
                <div class="flex items-center gap-2">
                  <div v-if="contact.avatar_url" class="h-8 w-8 overflow-hidden rounded-full">
                    <img :src="contact.avatar_url" :alt="contact.display_name" class="h-full w-full object-cover" />
                  </div>
                  <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-600">{{ contact.display_name.charAt(0).toUpperCase() }}</div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ contact.display_name }}</p>
                    <p v-if="contact.username" class="text-xs text-gray-400">@{{ contact.username }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-2.5">
                <span :class="platformColors[contact.platform] || 'bg-gray-100 text-gray-700'" class="rounded px-2 py-0.5 text-xs capitalize">{{ contact.platform }}</span>
              </td>
              <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ contact.interaction_count }}</td>
              <td class="px-4 py-2.5 text-sm text-gray-600">{{ formatDate(contact.last_seen_at) }}</td>
              <td class="px-4 py-2.5 text-right">
                <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click.stop="deleteContact(contact)"><i class="pi pi-trash text-sm" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </AppCard>
    </div>

    <!-- Detail Panel -->
    <div v-if="selectedContact" class="w-80 shrink-0">
      <AppCard>
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900">Contact Details</h3>
          <button class="text-gray-400 hover:text-gray-600" @click="selectedContact = null"><i class="pi pi-times" /></button>
        </div>

        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
          <div v-if="selectedContact.avatar_url" class="h-12 w-12 overflow-hidden rounded-full">
            <img :src="selectedContact.avatar_url" :alt="selectedContact.display_name" class="h-full w-full object-cover" />
          </div>
          <div v-else class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-lg font-medium text-gray-600">{{ selectedContact.display_name.charAt(0).toUpperCase() }}</div>
          <div>
            <p class="font-medium text-gray-900">{{ selectedContact.display_name }}</p>
            <span :class="platformColors[selectedContact.platform] || 'bg-gray-100 text-gray-700'" class="rounded px-2 py-0.5 text-xs capitalize">{{ selectedContact.platform }}</span>
          </div>
        </div>

        <div class="mt-4 space-y-3 text-sm">
          <div v-if="selectedContact.username"><span class="text-gray-500">Username:</span> <span class="text-gray-900">@{{ selectedContact.username }}</span></div>
          <div v-if="selectedContact.email"><span class="text-gray-500">Email:</span> <span class="text-gray-900">{{ selectedContact.email }}</span></div>
          <div v-if="selectedContact.phone"><span class="text-gray-500">Phone:</span> <span class="text-gray-900">{{ selectedContact.phone }}</span></div>
          <div><span class="text-gray-500">Interactions:</span> <span class="font-medium text-gray-900">{{ selectedContact.interaction_count }}</span></div>
          <div><span class="text-gray-500">First seen:</span> <span class="text-gray-900">{{ formatDate(selectedContact.first_seen_at) }}</span></div>
          <div><span class="text-gray-500">Last seen:</span> <span class="text-gray-900">{{ formatDate(selectedContact.last_seen_at) }}</span></div>
          <div v-if="selectedContact.tags && selectedContact.tags.length > 0">
            <span class="text-gray-500">Tags:</span>
            <div class="mt-1 flex flex-wrap gap-1">
              <span v-for="tag in selectedContact.tags" :key="tag" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ tag }}</span>
            </div>
          </div>
          <div v-if="selectedContact.notes">
            <span class="text-gray-500">Notes:</span>
            <p class="mt-1 whitespace-pre-wrap text-gray-700">{{ selectedContact.notes }}</p>
          </div>
        </div>
      </AppCard>
    </div>
  </div>
</template>
