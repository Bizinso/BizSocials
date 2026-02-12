<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import dayjs from 'dayjs'

const props = defineProps<{
  startDate?: string
  endDate?: string
}>()

const emit = defineEmits<{
  'update:startDate': [value: string]
  'update:endDate': [value: string]
  change: [range: { start_date: string; end_date: string }]
}>()

const presetOptions = [
  { label: 'Last 7 days', value: '7d' },
  { label: 'Last 14 days', value: '14d' },
  { label: 'Last 30 days', value: '30d' },
  { label: 'Last 90 days', value: '90d' },
  { label: 'This month', value: 'this_month' },
  { label: 'Last month', value: 'last_month' },
  { label: 'Custom', value: 'custom' },
]

const selectedPreset = ref('30d')
const customStart = ref<Date | null>(null)
const customEnd = ref<Date | null>(null)

const isCustom = computed(() => selectedPreset.value === 'custom')

function applyPreset(preset: string) {
  selectedPreset.value = preset
  let start: dayjs.Dayjs
  let end: dayjs.Dayjs = dayjs()

  switch (preset) {
    case '7d':
      start = dayjs().subtract(7, 'day')
      break
    case '14d':
      start = dayjs().subtract(14, 'day')
      break
    case '30d':
      start = dayjs().subtract(30, 'day')
      break
    case '90d':
      start = dayjs().subtract(90, 'day')
      break
    case 'this_month':
      start = dayjs().startOf('month')
      end = dayjs()
      break
    case 'last_month':
      start = dayjs().subtract(1, 'month').startOf('month')
      end = dayjs().subtract(1, 'month').endOf('month')
      break
    default:
      return
  }

  emitRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'))
}

function onCustomDateChange() {
  if (customStart.value && customEnd.value) {
    emitRange(
      dayjs(customStart.value).format('YYYY-MM-DD'),
      dayjs(customEnd.value).format('YYYY-MM-DD'),
    )
  }
}

function emitRange(start: string, end: string) {
  emit('update:startDate', start)
  emit('update:endDate', end)
  emit('change', { start_date: start, end_date: end })
}

watch(selectedPreset, (val) => {
  if (val !== 'custom') applyPreset(val)
})
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <Select
      v-model="selectedPreset"
      :options="presetOptions"
      option-label="label"
      option-value="value"
      placeholder="Date range"
      class="w-44"
    />
    <template v-if="isCustom">
      <DatePicker
        v-model="customStart"
        placeholder="Start date"
        date-format="yy-mm-dd"
        class="w-36"
        @date-select="onCustomDateChange"
      />
      <span class="text-gray-400">to</span>
      <DatePicker
        v-model="customEnd"
        placeholder="End date"
        date-format="yy-mm-dd"
        :min-date="customStart || undefined"
        class="w-36"
        @date-select="onCustomDateChange"
      />
    </template>
  </div>
</template>
