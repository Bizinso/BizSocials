<script setup lang="ts">
import { useRouter } from 'vue-router'
import AppLogo from '@/components/shared/AppLogo.vue'
import Button from 'primevue/button'

const router = useRouter()
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Public Header -->
    <header class="border-b border-gray-200 bg-white">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <router-link to="/" class="flex items-center gap-2">
          <AppLogo class="h-8 w-auto" />
        </router-link>

        <nav class="hidden items-center gap-6 md:flex">
          <router-link to="/kb" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            Knowledge Base
          </router-link>
          <router-link to="/feedback" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            Feedback
          </router-link>
          <router-link to="/roadmap" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            Roadmap
          </router-link>
          <router-link to="/changelog" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            Changelog
          </router-link>
        </nav>

        <div class="flex items-center gap-3">
          <Button label="Sign In" severity="secondary" size="small" @click="router.push('/login')" />
          <Button label="Get Started" size="small" @click="router.push('/register')" />
        </div>
      </div>
    </header>

    <!-- Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 bg-gray-50 py-8">
      <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
        &copy; {{ new Date().getFullYear() }} BizSocials. All rights reserved.
      </div>
    </footer>
  </div>
</template>
