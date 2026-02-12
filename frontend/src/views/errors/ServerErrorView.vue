<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4">
    <div class="text-center">
      <p class="text-6xl font-bold text-red-500">500</p>
      <h1 class="mt-4 text-3xl font-bold text-gray-900">Server error</h1>
      <p class="mt-2 text-gray-500">Something went wrong on our end. Please try again later.</p>
      <div class="mt-8 flex justify-center gap-3">
        <Button label="Go back" severity="secondary" outlined @click="router.back()" />
        <Button label="Home" @click="router.push('/')" />
      </div>
    </div>
  </div>
</template>
