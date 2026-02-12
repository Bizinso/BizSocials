<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePermissions } from '@/composables/usePermissions'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import WorkspaceMemberList from '@/components/workspace/WorkspaceMemberList.vue'
import WorkspaceMemberInvite from '@/components/workspace/WorkspaceMemberInvite.vue'

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)
const { canManageMembers } = usePermissions()

const memberListKey = ref(0)

function refreshMembers() {
  memberListKey.value++
}
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 py-6">
    <AppPageHeader title="Members" description="Manage workspace members and their roles" />

    <AppCard>
      <WorkspaceMemberInvite
        v-if="canManageMembers"
        :workspace-id="workspaceId"
        class="mb-6"
        @added="refreshMembers"
      />
      <WorkspaceMemberList :key="memberListKey" :workspace-id="workspaceId" />
    </AppCard>
  </div>
</template>
