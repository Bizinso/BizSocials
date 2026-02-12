<script setup lang="ts">
import { ref } from 'vue'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import TenantMemberList from '@/components/tenant/TenantMemberList.vue'
import TenantInvitationList from '@/components/tenant/TenantInvitationList.vue'
import InviteUserDialog from '@/components/tenant/InviteUserDialog.vue'
import Button from 'primevue/button'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'

const showInviteDialog = ref(false)
const memberListKey = ref(0)
const invitationListKey = ref(0)

function onInvited() {
  invitationListKey.value++
}
</script>

<template>
  <AppPageHeader title="Team Members" description="Manage who has access to your organization">
    <template #actions>
      <Button label="Invite member" icon="pi pi-user-plus" @click="showInviteDialog = true" />
    </template>
  </AppPageHeader>

  <TabView value="members">
    <TabPanel value="members" header="Members">
      <AppCard :padding="false">
        <TenantMemberList :key="memberListKey" />
      </AppCard>
    </TabPanel>
    <TabPanel value="invitations" header="Invitations">
      <AppCard :padding="false">
        <TenantInvitationList :key="invitationListKey" />
      </AppCard>
    </TabPanel>
  </TabView>

  <InviteUserDialog v-model:visible="showInviteDialog" @invited="onInvited" />
</template>
