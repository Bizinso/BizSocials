<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminPlanTable from '@/components/admin/AdminPlanTable.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminPlansApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { AdminPlanData } from '@/types/admin'

const toast = useToast()
const { confirmDelete } = useConfirm()
const plans = ref<AdminPlanData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    plans.value = await adminPlansApi.list()
  } finally {
    loading.value = false
  }
})

function editPlan(plan: AdminPlanData) {
  toast.info(`Edit plan: ${plan.name} (dialog would open here)`)
}

function removePlan(plan: AdminPlanData) {
  confirmDelete({
    message: `Delete plan "${plan.name}"? This cannot be undone.`,
    async onAccept() {
      try {
        await adminPlansApi.remove(plan.id)
        plans.value = plans.value.filter((p) => p.id !== plan.id)
        toast.success('Plan deleted')
      } catch { toast.error('Failed to delete plan') }
    },
  })
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Manage Plans</h1>

    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <AdminPlanTable v-else :plans="plans" @edit="editPlan" @remove="removePlan" />
  </div>
</template>
