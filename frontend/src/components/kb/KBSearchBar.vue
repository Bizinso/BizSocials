<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const router = useRouter()
const query = ref('')

function search() {
  if (query.value.trim()) {
    router.push({ name: 'kb-search', query: { q: query.value.trim() } })
  }
}
</script>

<template>
  <form class="flex items-center gap-2" @submit.prevent="search">
    <div class="relative flex-1">
      <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
      <InputText
        v-model="query"
        placeholder="Search articles..."
        class="w-full pl-10"
      />
    </div>
    <Button type="submit" label="Search" icon="pi pi-search" />
  </form>
</template>
