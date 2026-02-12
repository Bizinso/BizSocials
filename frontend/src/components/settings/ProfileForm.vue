<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { userApi } from '@/api/user'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const authStore = useAuthStore()
const toast = useToast()

const form = ref({
  name: '',
  timezone: '',
  phone: '',
  job_title: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

onMounted(() => {
  if (authStore.user) {
    form.value = {
      name: authStore.user.name,
      timezone: authStore.user.timezone || '',
      phone: '',
      job_title: '',
    }
  }
})

async function handleSave() {
  loading.value = true
  errors.value = {}
  try {
    const updated = await userApi.updateProfile({
      name: form.value.name,
      timezone: form.value.timezone || null,
      phone: form.value.phone || null,
      job_title: form.value.job_title || null,
    })
    authStore.user = updated
    toast.success('Profile updated!')
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
      <label for="profile-name" class="mb-1 block text-sm font-medium text-gray-700">Full name</label>
      <InputText id="profile-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
      <small v-if="errors.name" class="mt-1 text-red-500">{{ errors.name[0] }}</small>
    </div>

    <div>
      <label for="profile-email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
      <InputText :model-value="authStore.user?.email" disabled class="w-full" />
      <small class="mt-1 text-gray-400">Email cannot be changed.</small>
    </div>

    <div>
      <label for="profile-timezone" class="mb-1 block text-sm font-medium text-gray-700">Timezone</label>
      <InputText id="profile-timezone" v-model="form.timezone" placeholder="Asia/Kolkata" class="w-full" />
    </div>

    <div>
      <label for="profile-phone" class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
      <InputText id="profile-phone" v-model="form.phone" placeholder="+91..." class="w-full" />
    </div>

    <div>
      <label for="profile-title" class="mb-1 block text-sm font-medium text-gray-700">Job title</label>
      <InputText id="profile-title" v-model="form.job_title" placeholder="Marketing Manager" class="w-full" />
    </div>

    <Button type="submit" label="Save changes" :loading="loading" />
  </form>
</template>
