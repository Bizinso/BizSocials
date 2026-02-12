<script setup lang="ts">
import AppLogo from '@/components/shared/AppLogo.vue'
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">
      <div class="text-center">
        <AppLogo class="mx-auto h-10 w-auto" />
      </div>
      <slot />
    </div>
  </div>
</template>
