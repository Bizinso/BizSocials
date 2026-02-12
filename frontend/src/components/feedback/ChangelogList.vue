<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { ReleaseNoteData } from '@/types/feedback'
import ChangelogEntry from './ChangelogEntry.vue'

defineProps<{
  releases: ReleaseNoteData[]
}>()

const router = useRouter()

function openRelease(release: ReleaseNoteData) {
  router.push({ name: 'changelog-detail', params: { slug: release.version } })
}
</script>

<template>
  <div class="space-y-6">
    <div
      v-for="release in releases"
      :key="release.id"
      class="cursor-pointer"
      @click="openRelease(release)"
    >
      <ChangelogEntry :release="release" />
    </div>
    <p v-if="releases.length === 0" class="py-8 text-center text-sm text-gray-500">
      No releases published yet.
    </p>
  </div>
</template>
