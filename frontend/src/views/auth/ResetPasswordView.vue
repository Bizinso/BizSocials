<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue'
import ResetPasswordForm from '@/components/auth/ResetPasswordForm.vue'
</script>

<template>
  <AuthLayout>
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
      <h2 class="mb-6 text-center text-2xl font-bold text-gray-900">Reset your password</h2>
      <ResetPasswordForm />
    </div>
  </AuthLayout>
</template>
