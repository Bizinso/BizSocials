<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { aiApi } from '@/api/ai'
import Button from 'primevue/button'
import Drawer from 'primevue/drawer'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'insert-caption', caption: string): void
  (e: 'insert-hashtags', hashtags: string[]): void
}>()

const route = useRoute()
const workspaceId = computed(() => route.params.workspaceId as string)

const loading = ref(false)
const activeTab = ref('caption')

// Caption state
const captionTopic = ref('')
const captionPlatform = ref('instagram')
const captionTone = ref('')
const generatedCaption = ref('')

// Hashtags state
const hashtagContent = ref('')
const hashtagPlatform = ref('instagram')
const hashtagCount = ref(10)
const generatedHashtags = ref<string[]>([])

// Improve state
const improveContent = ref('')
const improveInstruction = ref('')
const improvedContent = ref('')

// Ideas state
const ideasTopic = ref('')
const ideasPlatform = ref('linkedin')
const ideasCount = ref(5)
const generatedIdeas = ref<string[]>([])

const platformOptions = [
  { label: 'Instagram', value: 'instagram' },
  { label: 'Twitter/X', value: 'twitter' },
  { label: 'LinkedIn', value: 'linkedin' },
  { label: 'Facebook', value: 'facebook' },
  { label: 'TikTok', value: 'tiktok' },
]

const toneOptions = [
  { label: 'None', value: '' },
  { label: 'Professional', value: 'professional' },
  { label: 'Casual', value: 'casual' },
  { label: 'Humorous', value: 'humorous' },
  { label: 'Inspiring', value: 'inspiring' },
  { label: 'Urgent', value: 'urgent' },
]

const error = ref('')

async function handleGenerateCaption() {
  if (!captionTopic.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const result = await aiApi.generateCaption(workspaceId.value, {
      topic: captionTopic.value,
      platform: captionPlatform.value,
      tone: captionTone.value || undefined,
    })
    generatedCaption.value = result.caption
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to generate caption'
  } finally {
    loading.value = false
  }
}

async function handleSuggestHashtags() {
  if (!hashtagContent.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const result = await aiApi.suggestHashtags(workspaceId.value, {
      content: hashtagContent.value,
      platform: hashtagPlatform.value,
      count: hashtagCount.value,
    })
    generatedHashtags.value = result.hashtags
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to suggest hashtags'
  } finally {
    loading.value = false
  }
}

async function handleImproveContent() {
  if (!improveContent.value.trim() || !improveInstruction.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const result = await aiApi.improveContent(workspaceId.value, {
      content: improveContent.value,
      instruction: improveInstruction.value,
    })
    improvedContent.value = result.content
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to improve content'
  } finally {
    loading.value = false
  }
}

async function handleGenerateIdeas() {
  if (!ideasTopic.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const result = await aiApi.generateIdeas(workspaceId.value, {
      topic: ideasTopic.value,
      platform: ideasPlatform.value,
      count: ideasCount.value,
    })
    generatedIdeas.value = result.ideas
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to generate ideas'
  } finally {
    loading.value = false
  }
}

function insertCaption() {
  if (generatedCaption.value) {
    emit('insert-caption', generatedCaption.value)
  }
}

function insertHashtags() {
  if (generatedHashtags.value.length > 0) {
    emit('insert-hashtags', generatedHashtags.value)
  }
}

function insertImproved() {
  if (improvedContent.value) {
    emit('insert-caption', improvedContent.value)
  }
}

function insertIdea(idea: string) {
  emit('insert-caption', idea)
}
</script>

<template>
  <Drawer
    :visible="props.visible"
    position="right"
    header="AI Assist"
    class="w-full md:w-[480px]"
    @update:visible="emit('update:visible', $event)"
  >
    <div v-if="error" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
      {{ error }}
    </div>

    <TabView :value="activeTab">
      <TabPanel value="caption" header="Caption">
        <div class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Topic</label>
            <InputText v-model="captionTopic" placeholder="e.g., New product launch" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Platform</label>
            <Select v-model="captionPlatform" :options="platformOptions" option-label="label" option-value="value" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Tone</label>
            <Select v-model="captionTone" :options="toneOptions" option-label="label" option-value="value" class="w-full" />
          </div>
          <Button label="Generate Caption" icon="pi pi-sparkles" :loading="loading" @click="handleGenerateCaption" class="w-full" />
          <div v-if="generatedCaption" class="rounded-md border border-gray-200 bg-gray-50 p-3">
            <p class="mb-2 whitespace-pre-wrap text-sm">{{ generatedCaption }}</p>
            <Button label="Insert" icon="pi pi-plus" size="small" severity="secondary" @click="insertCaption" />
          </div>
        </div>
      </TabPanel>

      <TabPanel value="hashtags" header="Hashtags">
        <div class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Content</label>
            <Textarea v-model="hashtagContent" placeholder="Paste your post content..." rows="3" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Platform</label>
            <Select v-model="hashtagPlatform" :options="platformOptions" option-label="label" option-value="value" class="w-full" />
          </div>
          <Button label="Suggest Hashtags" icon="pi pi-hashtag" :loading="loading" @click="handleSuggestHashtags" class="w-full" />
          <div v-if="generatedHashtags.length > 0" class="rounded-md border border-gray-200 bg-gray-50 p-3">
            <div class="mb-2 flex flex-wrap gap-2">
              <span
                v-for="tag in generatedHashtags"
                :key="tag"
                class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
              >
                {{ tag }}
              </span>
            </div>
            <Button label="Insert All" icon="pi pi-plus" size="small" severity="secondary" @click="insertHashtags" />
          </div>
        </div>
      </TabPanel>

      <TabPanel value="improve" header="Improve">
        <div class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Current Content</label>
            <Textarea v-model="improveContent" placeholder="Paste your content to improve..." rows="3" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Instruction</label>
            <InputText v-model="improveInstruction" placeholder="e.g., Make it more engaging" class="w-full" />
          </div>
          <Button label="Improve Content" icon="pi pi-pencil" :loading="loading" @click="handleImproveContent" class="w-full" />
          <div v-if="improvedContent" class="rounded-md border border-gray-200 bg-gray-50 p-3">
            <p class="mb-2 whitespace-pre-wrap text-sm">{{ improvedContent }}</p>
            <Button label="Insert" icon="pi pi-plus" size="small" severity="secondary" @click="insertImproved" />
          </div>
        </div>
      </TabPanel>

      <TabPanel value="ideas" header="Ideas">
        <div class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Topic</label>
            <InputText v-model="ideasTopic" placeholder="e.g., Digital marketing trends" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Platform</label>
            <Select v-model="ideasPlatform" :options="platformOptions" option-label="label" option-value="value" class="w-full" />
          </div>
          <Button label="Generate Ideas" icon="pi pi-lightbulb" :loading="loading" @click="handleGenerateIdeas" class="w-full" />
          <div v-if="generatedIdeas.length > 0" class="space-y-2">
            <div
              v-for="(idea, index) in generatedIdeas"
              :key="index"
              class="flex items-start justify-between rounded-md border border-gray-200 bg-gray-50 p-3"
            >
              <p class="mr-2 flex-1 text-sm">{{ idea }}</p>
              <Button icon="pi pi-plus" size="small" severity="secondary" rounded @click="insertIdea(idea)" />
            </div>
          </div>
        </div>
      </TabPanel>
    </TabView>
  </Drawer>
</template>
