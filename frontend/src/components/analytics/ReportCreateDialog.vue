<script setup lang="ts">
import { ref } from 'vue'
import { reportsApi } from '@/api/reports'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { ReportType } from '@/types/enums'
import type { ReportData, CreateReportRequest } from '@/types/report'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import dayjs from 'dayjs'

const props = defineProps<{
  visible: boolean
  workspaceId: string
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  created: [report: ReportData]
}>()

const toast = useToast()
const saving = ref(false)

const name = ref('')
const description = ref('')
const reportType = ref<ReportType>(ReportType.Performance)
const dateRange = ref<Date[]>([
  dayjs().subtract(30, 'day').toDate(),
  dayjs().toDate(),
])

const reportTypeOptions = [
  { label: 'Performance', value: ReportType.Performance },
  { label: 'Engagement', value: ReportType.Engagement },
  { label: 'Growth', value: ReportType.Growth },
  { label: 'Content', value: ReportType.Content },
  { label: 'Audience', value: ReportType.Audience },
  { label: 'Custom', value: ReportType.Custom },
]

async function create() {
  if (!name.value.trim() || dateRange.value.length < 2) return
  saving.value = true
  try {
    const data: CreateReportRequest = {
      name: name.value,
      description: description.value || null,
      report_type: reportType.value,
      date_from: dayjs(dateRange.value[0]).format('YYYY-MM-DD'),
      date_to: dayjs(dateRange.value[1]).format('YYYY-MM-DD'),
    }
    const report = await reportsApi.create(props.workspaceId, data)
    toast.success('Report generation started')
    emit('created', report)
    emit('update:visible', false)
    resetForm()
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

function resetForm() {
  name.value = ''
  description.value = ''
  reportType.value = ReportType.Performance
  dateRange.value = [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()]
}
</script>

<template>
  <Dialog
    :visible="visible"
    header="Generate Report"
    :style="{ width: '500px' }"
    modal
    @update:visible="emit('update:visible', $event)"
  >
    <div class="space-y-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Report Name</label>
        <InputText v-model="name" placeholder="e.g. Monthly Performance Report" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
        <Select
          v-model="reportType"
          :options="reportTypeOptions"
          option-label="label"
          option-value="value"
          class="w-full"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Date Range</label>
        <DatePicker
          v-model="dateRange"
          selection-mode="range"
          date-format="yy-mm-dd"
          placeholder="Select date range"
          class="w-full"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Description (optional)</label>
        <Textarea v-model="description" rows="2" class="w-full" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="emit('update:visible', false)" />
      <Button label="Generate" icon="pi pi-file-pdf" :disabled="!name.trim()" :loading="saving" @click="create" />
    </template>
  </Dialog>
</template>
