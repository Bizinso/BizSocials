<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/api/tenant'
import { useTenantStore } from '@/stores/tenant'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'

const tenantStore = useTenantStore()
const toast = useToast()

const form = ref({
  name: '',
  website: '',
  timezone: '',
  industry: '',
  company_size: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

const companySizeOptions = [
  { label: 'Solo (1)', value: 'solo' },
  { label: 'Small (2–10)', value: 'small' },
  { label: 'Medium (11–50)', value: 'medium' },
  { label: 'Large (51–200)', value: 'large' },
  { label: 'Enterprise (200+)', value: 'enterprise' },
]

onMounted(() => {
  if (tenantStore.tenant) {
    form.value = {
      name: tenantStore.tenant.name,
      website: tenantStore.tenant.website || '',
      timezone: tenantStore.tenant.timezone || '',
      industry: '',
      company_size: '',
    }
  }
})

async function handleSave() {
  loading.value = true
  errors.value = {}
  try {
    const updated = await tenantApi.update({
      name: form.value.name,
      website: form.value.website || null,
      timezone: form.value.timezone || null,
      industry: form.value.industry || null,
      company_size: form.value.company_size || null,
    })
    tenantStore.tenant = updated
    toast.success('Organization settings updated!')
  } catch (e) {
    const err = parseApiError(e)
    errors.value = err.errors
    if (!Object.keys(err.errors).length) {
      toast.error(err.message)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSave" class="max-w-lg space-y-4">
    <div>
      <label for="org-name" class="mb-1 block text-sm font-medium text-gray-700">Organization name</label>
      <InputText id="org-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
      <small v-if="errors.name" class="mt-1 text-red-500">{{ errors.name[0] }}</small>
    </div>

    <div>
      <label for="org-website" class="mb-1 block text-sm font-medium text-gray-700">Website</label>
      <InputText id="org-website" v-model="form.website" type="url" placeholder="https://..." class="w-full" />
    </div>

    <div>
      <label for="org-timezone" class="mb-1 block text-sm font-medium text-gray-700">Timezone</label>
      <InputText id="org-timezone" v-model="form.timezone" placeholder="Asia/Kolkata" class="w-full" />
    </div>

    <div>
      <label for="org-size" class="mb-1 block text-sm font-medium text-gray-700">Company size</label>
      <Select
        id="org-size"
        v-model="form.company_size"
        :options="companySizeOptions"
        option-label="label"
        option-value="value"
        placeholder="Select size"
        class="w-full"
      />
    </div>

    <Button type="submit" label="Save changes" :loading="loading" />
  </form>
</template>
