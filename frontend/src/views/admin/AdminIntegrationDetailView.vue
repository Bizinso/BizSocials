<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Textarea from 'primevue/textarea'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminIntegrationsApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type {
  IntegrationDetail,
  IntegrationHealthSummary,
  IntegrationHealthAccount,
} from '@/types/admin'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { confirmAction } = useConfirm()

const provider = computed(() => route.params.provider as string)
const integration = ref<IntegrationDetail | null>(null)
const healthSummary = ref<Record<string, IntegrationHealthSummary>>({})
const healthAccounts = ref<IntegrationHealthAccount[]>([])
const loading = ref(true)
const verifying = ref(false)
const toggling = ref(false)

// Force reauth state
const reauthReason = ref('')
const reauthPlatforms = ref<string[]>([])
const reauthLoading = ref(false)

onMounted(async () => {
  try {
    integration.value = await adminIntegrationsApi.get(provider.value)
    const health = await adminIntegrationsApi.getHealth(provider.value)
    healthSummary.value = health.summary
    healthAccounts.value = health.accounts?.data ?? []
  } catch {
    toast.error('Integration not found')
    router.push({ name: 'admin-integrations' })
  } finally {
    loading.value = false
  }
})

function statusSeverity(status: string): 'success' | 'warn' | 'danger' | 'secondary' {
  switch (status) {
    case 'active':
    case 'connected':
      return 'success'
    case 'maintenance':
    case 'token_expired':
      return 'warn'
    case 'disabled':
    case 'revoked':
    case 'disconnected':
      return 'danger'
    default:
      return 'secondary'
  }
}

async function verifyCredentials() {
  verifying.value = true
  try {
    const result = await adminIntegrationsApi.verify(provider.value)
    if (result.valid) {
      toast.success(`Credentials valid${result.app_name ? ` (${result.app_name})` : ''}`)
      if (integration.value) {
        integration.value.last_verified_at = result.verified_at
      }
    } else {
      toast.error(result.error ?? 'Credentials verification failed')
    }
  } catch {
    toast.error('Failed to verify credentials')
  } finally {
    verifying.value = false
  }
}

async function toggleIntegration() {
  if (!integration.value) return
  const enabling = !integration.value.is_enabled

  if (!enabling) {
    confirmAction({
      header: 'Disable Integration',
      message: 'Disabling will disconnect all active social accounts for this provider. Continue?',
      async onAccept() {
        await doToggle(false, 'Disabled by admin')
      },
    })
  } else {
    await doToggle(true)
  }
}

async function doToggle(enabled: boolean, reason?: string) {
  toggling.value = true
  try {
    const result = await adminIntegrationsApi.toggle(provider.value, enabled, reason)
    if (integration.value) {
      integration.value.is_enabled = result.is_enabled
      integration.value.status = result.status
    }
    toast.success(enabled ? 'Integration enabled' : 'Integration disabled')
  } catch {
    toast.error('Failed to toggle integration')
  } finally {
    toggling.value = false
  }
}

async function forceReauth() {
  if (reauthPlatforms.value.length === 0) {
    toast.warn('Select at least one platform')
    return
  }
  if (reauthReason.value.length < 10) {
    toast.warn('Reason must be at least 10 characters')
    return
  }

  confirmAction({
    header: 'Force Re-authorization',
    message: `This will revoke all ${reauthPlatforms.value.join(' and ')} tokens and require tenants to reconnect. Continue?`,
    async onAccept() {
      reauthLoading.value = true
      try {
        const result = await adminIntegrationsApi.forceReauth(
          provider.value,
          reauthPlatforms.value,
          reauthReason.value,
        )
        toast.success(`Revoked ${result.accounts_revoked} accounts across ${result.tenants_affected} tenants`)
        reauthReason.value = ''
        reauthPlatforms.value = []

        // Refresh health data
        const health = await adminIntegrationsApi.getHealth(provider.value)
        healthSummary.value = health.summary
        healthAccounts.value = health.accounts?.data ?? []
      } catch {
        toast.error('Force re-authorization failed')
      } finally {
        reauthLoading.value = false
      }
    },
  })
}

function togglePlatform(platform: string) {
  const idx = reauthPlatforms.value.indexOf(platform)
  if (idx >= 0) {
    reauthPlatforms.value.splice(idx, 1)
  } else {
    reauthPlatforms.value.push(platform)
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <Button icon="pi pi-arrow-left" severity="secondary" text size="small" @click="router.push({ name: 'admin-integrations' })" />
      <h1 class="text-2xl font-bold text-gray-900">
        {{ integration?.display_name ?? 'Integration' }}
      </h1>
      <Tag v-if="integration" :value="integration.status" :severity="statusSeverity(integration.status)" class="ml-2" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="10" />

    <template v-else-if="integration">
      <TabView>
        <!-- ─── Credentials Tab ──────────────────────── -->
        <TabPanel header="Credentials">
          <div class="space-y-6">
            <!-- Status & Actions -->
            <div class="flex items-center gap-3">
              <Button
                :label="integration.is_enabled ? 'Disable' : 'Enable'"
                :icon="integration.is_enabled ? 'pi pi-ban' : 'pi pi-check'"
                :severity="integration.is_enabled ? 'danger' : 'success'"
                size="small"
                :loading="toggling"
                @click="toggleIntegration"
              />
              <Button
                label="Verify Credentials"
                icon="pi pi-shield"
                severity="secondary"
                size="small"
                :loading="verifying"
                @click="verifyCredentials"
              />
            </div>

            <!-- Detail Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">App ID (Masked)</label>
                <div class="mt-1 font-mono text-sm text-gray-900">{{ integration.app_id_masked }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">App Secret</label>
                <div class="mt-1 text-sm text-gray-900">
                  <Tag :value="integration.has_secret ? 'Configured' : 'Not Set'" :severity="integration.has_secret ? 'success' : 'danger'" />
                </div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">API Version</label>
                <div class="mt-1 text-sm text-gray-900">{{ integration.api_version }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">Environment</label>
                <div class="mt-1 text-sm text-gray-900 capitalize">{{ integration.environment }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">Last Verified</label>
                <div class="mt-1 text-sm text-gray-900">
                  {{ integration.last_verified_at ? new Date(integration.last_verified_at).toLocaleString() : 'Never' }}
                </div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="text-xs font-semibold uppercase text-gray-500">Last Rotated</label>
                <div class="mt-1 text-sm text-gray-900">
                  {{ integration.last_rotated_at ? new Date(integration.last_rotated_at).toLocaleString() : 'Never' }}
                </div>
              </div>
            </div>

            <!-- Scopes -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
              <h3 class="mb-3 text-sm font-semibold text-gray-700">Scopes by Platform</h3>
              <div v-for="(scopes, platform) in integration.scopes" :key="platform" class="mb-3">
                <div class="mb-1 text-xs font-medium uppercase text-gray-500">{{ platform }}</div>
                <div class="flex flex-wrap gap-1">
                  <Tag v-for="scope in scopes" :key="scope" :value="scope" severity="secondary" />
                </div>
              </div>
            </div>

            <!-- Redirect URIs -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
              <h3 class="mb-3 text-sm font-semibold text-gray-700">Redirect URIs</h3>
              <div v-for="(uri, platform) in integration.redirect_uris" :key="platform" class="mb-2">
                <span class="text-xs font-medium uppercase text-gray-500">{{ platform }}:</span>
                <code class="ml-2 text-xs text-gray-700">{{ uri }}</code>
              </div>
            </div>

            <!-- Updated By -->
            <div v-if="integration.updated_by" class="text-xs text-gray-500">
              Last updated by {{ integration.updated_by.name }} on {{ new Date(integration.updated_at).toLocaleString() }}
            </div>
          </div>
        </TabPanel>

        <!-- ─── Health Tab ───────────────────────────── -->
        <TabPanel header="Health">
          <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
              <template v-for="(stats, platform) in healthSummary" :key="platform">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                  <div class="mb-2 text-xs font-semibold uppercase text-gray-500">{{ platform }}</div>
                  <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Connected</span>
                      <span class="font-medium text-green-600">{{ stats.connected }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Expiring</span>
                      <span class="font-medium text-yellow-600">{{ stats.expiring }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Expired</span>
                      <span class="font-medium text-orange-600">{{ stats.expired }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Revoked</span>
                      <span class="font-medium text-red-600">{{ stats.revoked }}</span>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Account Table -->
            <DataTable :value="healthAccounts" responsive-layout="scroll" class="rounded-lg border border-gray-200">
              <Column field="account_name" header="Account" sortable />
              <Column field="platform" header="Platform" sortable>
                <template #body="{ data }">
                  <span class="capitalize">{{ data.platform }}</span>
                </template>
              </Column>
              <Column field="tenant_name" header="Tenant" sortable />
              <Column field="workspace_name" header="Workspace" sortable />
              <Column field="status" header="Status" sortable>
                <template #body="{ data }">
                  <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                </template>
              </Column>
              <Column header="Token Expires">
                <template #body="{ data }">
                  <span v-if="data.token_expires_at" class="text-sm">
                    {{ new Date(data.token_expires_at).toLocaleDateString() }}
                  </span>
                  <span v-else class="text-sm text-gray-400">N/A</span>
                </template>
              </Column>
            </DataTable>

            <!-- Force Reauth Section -->
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
              <h3 class="mb-3 text-sm font-semibold text-orange-800">Force Re-authorization</h3>
              <p class="mb-3 text-xs text-orange-700">
                This will revoke all tokens for selected platforms and require tenants to reconnect their accounts.
              </p>

              <div class="mb-3 flex gap-2">
                <Button
                  v-for="platform in integration?.platforms ?? []"
                  :key="platform"
                  :label="platform.charAt(0).toUpperCase() + platform.slice(1)"
                  :severity="reauthPlatforms.includes(platform) ? 'warn' : 'secondary'"
                  :outlined="!reauthPlatforms.includes(platform)"
                  size="small"
                  @click="togglePlatform(platform)"
                />
              </div>

              <Textarea
                v-model="reauthReason"
                placeholder="Reason for forced re-authorization (min 10 characters)..."
                rows="2"
                class="mb-3 w-full"
              />

              <Button
                label="Force Re-authorization"
                icon="pi pi-exclamation-triangle"
                severity="warn"
                size="small"
                :loading="reauthLoading"
                :disabled="reauthPlatforms.length === 0 || reauthReason.length < 10"
                @click="forceReauth"
              />
            </div>
          </div>
        </TabPanel>

        <!-- ─── Audit Tab ────────────────────────────── -->
        <TabPanel header="Audit Log">
          <p class="text-sm text-gray-500">
            Audit trail for integration configuration changes is recorded automatically.
            Use the API endpoint <code class="rounded bg-gray-100 px-1 text-xs">GET /api/v1/admin/integrations/{{ provider }}/audit-log</code> for programmatic access.
          </p>
        </TabPanel>
      </TabView>
    </template>
  </div>
</template>
