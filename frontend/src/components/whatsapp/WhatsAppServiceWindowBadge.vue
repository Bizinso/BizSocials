<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps<{
  lastCustomerMessageAt: string | null
  isWithinServiceWindow: boolean
}>()

const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now()
  }, 60_000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const expiresAt = computed(() => {
  if (!props.lastCustomerMessageAt) return null
  return new Date(props.lastCustomerMessageAt).getTime() + 24 * 60 * 60 * 1000
})

const isActive = computed(() => {
  if (!expiresAt.value) return false
  return now.value < expiresAt.value
})

const remainingLabel = computed(() => {
  if (!expiresAt.value || !isActive.value) return 'Expired'
  const diff = expiresAt.value - now.value
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  if (hours > 0) return `${hours}h ${minutes}m left`
  return `${minutes}m left`
})

const badgeClass = computed(() => {
  if (!isActive.value) return 'bg-red-100 text-red-700'
  if (!expiresAt.value) return 'bg-gray-100 text-gray-600'
  const diff = expiresAt.value - now.value
  const hoursLeft = diff / (1000 * 60 * 60)
  if (hoursLeft <= 2) return 'bg-amber-100 text-amber-700'
  return 'bg-green-100 text-green-700'
})
</script>

<template>
  <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="badgeClass">
    <i class="pi pi-clock text-[10px]" />
    {{ remainingLabel }}
  </span>
</template>
