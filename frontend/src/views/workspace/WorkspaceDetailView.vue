<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { workspaceApi } from '@/api/workspace'
import { usePermissions } from '@/composables/usePermissions'
import type { WorkspaceData } from '@/types/workspace'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import WorkspaceSettings from '@/components/workspace/WorkspaceSettings.vue'
import WorkspaceMemberList from '@/components/workspace/WorkspaceMemberList.vue'
import WorkspaceMemberInvite from '@/components/workspace/WorkspaceMemberInvite.vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)

const { canManageMembers } = usePermissions()
const workspace = ref<WorkspaceData | null>(null)
const loading = ref(true)
const memberListKey = ref(0)

onMounted(async () => {
  try {
    workspace.value = await workspaceApi.get(workspaceId.value)
  } finally {
    loading.value = false
  }
})

function refreshMembers() {
  memberListKey.value++
}
</script>

<template>
  <div v-if="loading" class="space-y-4">
    <AppLoadingSkeleton :lines="2" />
    <AppLoadingSkeleton :lines="5" />
  </div>

  <template v-else-if="workspace">
    <AppPageHeader :title="workspace.name" :description="workspace.description || undefined" />

    <TabView value="settings">
      <TabPanel value="settings" header="Settings">
        <AppCard>
          <WorkspaceSettings :workspace="workspace" />
        </AppCard>
      </TabPanel>
      <TabPanel value="members" header="Members">
        <AppCard>
          <WorkspaceMemberInvite v-if="canManageMembers" :workspace-id="workspaceId" class="mb-6" @added="refreshMembers" />
          <WorkspaceMemberList :key="memberListKey" :workspace-id="workspaceId" />
        </AppCard>
      </TabPanel>
    </TabView>
  </template>
</template>
