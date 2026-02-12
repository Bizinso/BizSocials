<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { contentApi } from '@/api/content'
import type { PostData } from '@/types/content'
import PostCalendarEvent from './PostCalendarEvent.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Button from 'primevue/button'
import dayjs from 'dayjs'

const props = defineProps<{
  workspaceId: string
}>()

const emit = defineEmits<{
  'select-post': [post: PostData]
  'select-date': [date: string]
}>()

const currentMonth = ref(dayjs())
const posts = ref<PostData[]>([])
const loading = ref(false)

const monthLabel = computed(() => currentMonth.value.format('MMMM YYYY'))

const calendarDays = computed(() => {
  const start = currentMonth.value.startOf('month').startOf('week')
  const end = currentMonth.value.endOf('month').endOf('week')
  const days: dayjs.Dayjs[] = []
  let day = start
  while (day.isBefore(end) || day.isSame(end, 'day')) {
    days.push(day)
    day = day.add(1, 'day')
  }
  return days
})

const postsByDate = computed(() => {
  const map: Record<string, PostData[]> = {}
  for (const post of posts.value) {
    const date = post.scheduled_at || post.published_at || post.created_at
    const key = dayjs(date).format('YYYY-MM-DD')
    if (!map[key]) map[key] = []
    map[key].push(post)
  }
  return map
})

function isToday(day: dayjs.Dayjs) {
  return day.isSame(dayjs(), 'day')
}

function isCurrentMonth(day: dayjs.Dayjs) {
  return day.isSame(currentMonth.value, 'month')
}

function prevMonth() {
  currentMonth.value = currentMonth.value.subtract(1, 'month')
}

function nextMonth() {
  currentMonth.value = currentMonth.value.add(1, 'month')
}

function goToToday() {
  currentMonth.value = dayjs()
}

async function fetchPosts() {
  loading.value = true
  try {
    const response = await contentApi.listPosts(props.workspaceId, { per_page: 100 })
    posts.value = response.data
  } finally {
    loading.value = false
  }
}

watch(
  () => props.workspaceId,
  () => fetchPosts(),
)

onMounted(fetchPosts)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-chevron-left" text rounded size="small" @click="prevMonth" />
        <h3 class="text-lg font-semibold text-gray-900">{{ monthLabel }}</h3>
        <Button icon="pi pi-chevron-right" text rounded size="small" @click="nextMonth" />
      </div>
      <Button label="Today" severity="secondary" size="small" @click="goToToday" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="6" />

    <template v-else>
      <!-- Weekday headers -->
      <div class="mb-1 grid grid-cols-7 text-center text-xs font-medium text-gray-500">
        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="py-2">
          {{ day }}
        </div>
      </div>

      <!-- Days grid -->
      <div class="grid grid-cols-7 border-l border-t border-gray-200">
        <div
          v-for="day in calendarDays"
          :key="day.format('YYYY-MM-DD')"
          class="min-h-[100px] border-b border-r border-gray-200 p-1"
          :class="{
            'bg-white': isCurrentMonth(day),
            'bg-gray-50': !isCurrentMonth(day),
          }"
        >
          <div
            class="mb-1 text-right text-xs"
            :class="{
              'font-bold text-primary-600': isToday(day),
              'text-gray-400': !isCurrentMonth(day),
              'text-gray-700': isCurrentMonth(day) && !isToday(day),
            }"
          >
            {{ day.date() }}
          </div>
          <div class="space-y-1">
            <PostCalendarEvent
              v-for="post in (postsByDate[day.format('YYYY-MM-DD')] || []).slice(0, 3)"
              :key="post.id"
              :post="post"
              @click="emit('select-post', post)"
            />
            <p
              v-if="(postsByDate[day.format('YYYY-MM-DD')]?.length || 0) > 3"
              class="cursor-pointer text-center text-xs text-primary-600 hover:underline"
              @click="emit('select-date', day.format('YYYY-MM-DD'))"
            >
              +{{ (postsByDate[day.format('YYYY-MM-DD')]?.length || 0) - 3 }} more
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
