<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { notificationsApi } from '@/api/notifications'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { NotificationPreferenceData } from '@/types/notification'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'

const toast = useToast()
const preferences = ref<NotificationPreferenceData[]>([])
const loading = ref(false)
const saving = ref(false)

const groupedByCategory = computed(() => {
  const groups: Record<string, NotificationPreferenceData[]> = {}
  for (const pref of preferences.value) {
    if (!groups[pref.category]) groups[pref.category] = []
    groups[pref.category].push(pref)
  }
  return groups
})

onMounted(async () => {
  loading.value = true
  try {
    preferences.value = await notificationsApi.getPreferences()
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  try {
    const data = {
      preferences: preferences.value.map((p) => ({
        notification_type: p.notification_type,
        in_app_enabled: p.in_app_enabled,
        email_enabled: p.email_enabled,
        push_enabled: p.push_enabled,
        sms_enabled: p.sms_enabled,
      })),
    }
    preferences.value = await notificationsApi.updatePreferences(data)
    toast.success('Preferences saved')
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

function categoryLabel(category: string): string {
  return category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, ' ')
}
</script>

<template>
  <AppLoadingSkeleton v-if="loading" :lines="6" />

  <div v-else class="space-y-6">
    <div v-for="(prefs, category) in groupedByCategory" :key="category">
      <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
        {{ categoryLabel(category as string) }}
      </h3>

      <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left font-medium text-gray-600">Notification</th>
              <th class="w-20 px-4 py-2 text-center font-medium text-gray-600">In-App</th>
              <th class="w-20 px-4 py-2 text-center font-medium text-gray-600">Email</th>
              <th class="w-20 px-4 py-2 text-center font-medium text-gray-600">Push</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="pref in prefs"
              :key="pref.id"
              class="border-t border-gray-100"
            >
              <td class="px-4 py-2 text-gray-700">{{ pref.notification_type_label }}</td>
              <td class="px-4 py-2 text-center">
                <Checkbox v-model="pref.in_app_enabled" :binary="true" />
              </td>
              <td class="px-4 py-2 text-center">
                <Checkbox v-model="pref.email_enabled" :binary="true" />
              </td>
              <td class="px-4 py-2 text-center">
                <Checkbox v-model="pref.push_enabled" :binary="true" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="flex justify-end">
      <Button label="Save Preferences" icon="pi pi-save" :loading="saving" @click="save" />
    </div>
  </div>
</template>
