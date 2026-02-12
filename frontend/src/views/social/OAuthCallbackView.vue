<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { socialApi } from '@/api/social'
import { useSocialStore } from '@/stores/social'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { getPlatformLabel, getPlatformColor } from '@/utils/platform-config'
import type { OAuthExchangeResult, FacebookPage } from '@/types/social'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const socialStore = useSocialStore()

type Step = 'exchanging' | 'select-page' | 'connecting' | 'error'

const step = ref<Step>('exchanging')
const errorMessage = ref('')
const exchangeResult = ref<OAuthExchangeResult | null>(null)
const selectedPageId = ref<string | null>(null)
const connecting = ref(false)

const workspaceId = localStorage.getItem('oauth_workspace_id') || ''

onMounted(async () => {
  const error = route.query.error as string | undefined
  const errorDescription = route.query.error_description as string | undefined
  const platform = route.query.platform as string | undefined
  const code = route.query.code as string | undefined
  const state = route.query.state as string | undefined

  if (error) {
    step.value = 'error'
    errorMessage.value = errorDescription || 'Authorization was denied.'
    return
  }

  if (!platform || !code || !state) {
    step.value = 'error'
    errorMessage.value = 'Missing authorization parameters. Please try again.'
    return
  }

  if (!workspaceId) {
    step.value = 'error'
    errorMessage.value = 'No workspace selected. Please go back and try again.'
    return
  }

  try {
    // Exchange the authorization code for tokens and available pages
    const result = await socialApi.exchangeCode(platform, { code, state })
    exchangeResult.value = result

    // Facebook with multiple pages → show page picker
    if (platform === 'facebook' && result.pages && result.pages.length > 1) {
      selectedPageId.value = result.pages[0].id
      step.value = 'select-page'
      return
    }

    // Single page or Instagram → auto-connect
    await connectAccount(result.pages?.[0]?.id ?? null)
  } catch (e) {
    step.value = 'error'
    errorMessage.value = parseApiError(e).message
  }
})

async function connectAccount(pageId: string | null) {
  if (!exchangeResult.value) return

  step.value = 'connecting'
  connecting.value = true

  try {
    const platform = exchangeResult.value.platform
    await socialApi.connectOAuth(platform, {
      workspace_id: workspaceId,
      session_key: exchangeResult.value.session_key,
      page_id: pageId,
    })

    localStorage.removeItem('oauth_workspace_id')
    toast.success(`${getPlatformLabel(platform)} account connected!`)
    socialStore.fetchAccounts(workspaceId)
    router.push({ name: 'social-accounts', params: { workspaceId } })
  } catch (e) {
    step.value = 'error'
    errorMessage.value = parseApiError(e).message
    connecting.value = false
  }
}

function handlePageSelect() {
  connectAccount(selectedPageId.value)
}

function goBack() {
  localStorage.removeItem('oauth_workspace_id')
  if (workspaceId) {
    router.push({ name: 'social-accounts', params: { workspaceId } })
  } else {
    router.push({ name: 'dashboard' })
  }
}
</script>

<template>
  <div class="flex min-h-[60vh] items-center justify-center px-4">
    <!-- Exchanging / Connecting spinner -->
    <div
      v-if="step === 'exchanging' || step === 'connecting'"
      class="text-center"
    >
      <ProgressSpinner
        style="width: 48px; height: 48px"
        stroke-width="4"
      />
      <p class="mt-4 text-sm text-gray-600">
        {{ step === 'exchanging' ? 'Authorizing your account...' : 'Connecting your account...' }}
      </p>
    </div>

    <!-- Facebook Page Picker -->
    <div
      v-else-if="step === 'select-page' && exchangeResult?.pages"
      class="w-full max-w-md"
    >
      <div class="mb-6 text-center">
        <div
          class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full text-white"
          :style="{ backgroundColor: getPlatformColor('facebook') }"
        >
          <i class="pi pi-facebook text-xl" />
        </div>
        <h2 class="text-lg font-semibold text-gray-900">Select a Facebook Page</h2>
        <p class="mt-1 text-sm text-gray-500">
          Choose which page to connect to your workspace.
        </p>
      </div>

      <div class="space-y-2">
        <label
          v-for="page in exchangeResult.pages"
          :key="page.id"
          class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors"
          :class="selectedPageId === page.id
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-200 hover:bg-gray-50'"
        >
          <input
            v-model="selectedPageId"
            type="radio"
            name="page"
            :value="page.id"
            class="h-4 w-4 text-blue-600"
          />
          <div class="flex-1">
            <p class="font-medium text-gray-900">{{ page.name }}</p>
            <p class="text-xs text-gray-500">ID: {{ page.id }}</p>
          </div>
        </label>
      </div>

      <div class="mt-6 flex gap-3">
        <Button
          label="Cancel"
          severity="secondary"
          outlined
          class="flex-1"
          @click="goBack"
        />
        <Button
          label="Connect Page"
          icon="pi pi-check"
          class="flex-1"
          :loading="connecting"
          :disabled="!selectedPageId"
          @click="handlePageSelect"
        />
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="step === 'error'" class="w-full max-w-md text-center">
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
        <i class="pi pi-times text-xl text-red-600" />
      </div>
      <h2 class="text-lg font-semibold text-gray-900">Connection Failed</h2>
      <p class="mt-2 text-sm text-gray-600">{{ errorMessage }}</p>
      <Button
        label="Go Back"
        severity="secondary"
        outlined
        class="mt-4"
        @click="goBack"
      />
    </div>
  </div>
</template>
