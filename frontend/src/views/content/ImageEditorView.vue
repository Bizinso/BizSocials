<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { upload } from '@/api/client'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const imageFile = ref<File | null>(null)
const imagePreviewUrl = ref<string | null>(null)
const resultUrl = ref<string | null>(null)
const processing = ref(false)

type EditorAction = 'crop' | 'resize' | 'rotate' | 'flip' | 'text' | 'filter' | 'watermark'
const activeAction = ref<EditorAction | null>(null)

const cropForm = ref({ x: 0, y: 0, width: 300, height: 300 })
const resizeForm = ref({ width: 800, height: 600 })
const rotateForm = ref({ angle: 90 })
const flipForm = ref({ direction: 'horizontal' as 'horizontal' | 'vertical' })
const textForm = ref({ text: '', x: 10, y: 10, size: 24, color: '#000000' })
const filterForm = ref({ filter: 'grayscale' })
const watermarkForm = ref({ preset: 'logo-bottom-right' })

const filterOptions = ['grayscale', 'sepia', 'blur', 'sharpen', 'brightness', 'contrast']
const watermarkPresets = ['logo-bottom-right', 'logo-bottom-left', 'logo-center', 'text-bottom']

const actions: { key: EditorAction; label: string; icon: string }[] = [
  { key: 'crop', label: 'Crop', icon: 'pi pi-stop' },
  { key: 'resize', label: 'Resize', icon: 'pi pi-arrows-alt' },
  { key: 'rotate', label: 'Rotate', icon: 'pi pi-sync' },
  { key: 'flip', label: 'Flip', icon: 'pi pi-sort-alt' },
  { key: 'text', label: 'Add Text', icon: 'pi pi-pencil' },
  { key: 'filter', label: 'Filter', icon: 'pi pi-palette' },
  { key: 'watermark', label: 'Watermark', icon: 'pi pi-shield' },
]

function onFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length) {
    imageFile.value = target.files[0]
    imagePreviewUrl.value = URL.createObjectURL(target.files[0])
    resultUrl.value = null
    activeAction.value = null
  }
}

function selectAction(action: EditorAction) {
  activeAction.value = activeAction.value === action ? null : action
}

async function applyAction() {
  if (!imageFile.value || !activeAction.value) return
  processing.value = true
  try {
    const formData = new FormData()
    formData.append('image', imageFile.value)
    formData.append('action', activeAction.value)

    switch (activeAction.value) {
      case 'crop':
        formData.append('x', String(cropForm.value.x))
        formData.append('y', String(cropForm.value.y))
        formData.append('width', String(cropForm.value.width))
        formData.append('height', String(cropForm.value.height))
        break
      case 'resize':
        formData.append('width', String(resizeForm.value.width))
        formData.append('height', String(resizeForm.value.height))
        break
      case 'rotate':
        formData.append('angle', String(rotateForm.value.angle))
        break
      case 'flip':
        formData.append('direction', flipForm.value.direction)
        break
      case 'text':
        formData.append('text', textForm.value.text)
        formData.append('x', String(textForm.value.x))
        formData.append('y', String(textForm.value.y))
        formData.append('size', String(textForm.value.size))
        formData.append('color', textForm.value.color)
        break
      case 'filter':
        formData.append('filter', filterForm.value.filter)
        break
      case 'watermark':
        formData.append('preset', watermarkForm.value.preset)
        break
    }

    const res = await upload<{ url: string }>(`/workspaces/${workspaceId.value}/image-editor/apply`, formData)
    resultUrl.value = res.url
    toast.success('Action applied')
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { processing.value = false }
}

function downloadResult() {
  if (!resultUrl.value) return
  const a = document.createElement('a')
  a.href = resultUrl.value
  a.download = 'edited-image'
  a.click()
}
</script>

<template>
  <AppPageHeader title="Image Editor" description="Edit images with crop, resize, filters, and more" />

  <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <!-- Left Panel: Upload + Actions -->
    <div class="space-y-4">
      <!-- File Upload -->
      <AppCard>
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Upload Image</h3>
        <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 px-4 py-8 transition-colors hover:border-primary-400 hover:bg-primary-50">
          <i class="pi pi-cloud-upload mb-2 text-2xl text-gray-400" />
          <span class="text-sm text-gray-500">{{ imageFile ? imageFile.name : 'Click to upload an image' }}</span>
          <input type="file" accept="image/*" class="hidden" @change="onFileSelect" />
        </label>
      </AppCard>

      <!-- Actions -->
      <AppCard v-if="imageFile">
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Actions</h3>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="action in actions"
            :key="action.key"
            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
            :class="activeAction === action.key ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
            @click="selectAction(action.key)"
          >
            <i :class="action.icon" class="text-sm" />
            {{ action.label }}
          </button>
        </div>
      </AppCard>

      <!-- Action Form -->
      <AppCard v-if="activeAction">
        <h3 class="mb-3 text-sm font-semibold text-gray-700 capitalize">{{ activeAction }} Settings</h3>

        <!-- Crop -->
        <div v-if="activeAction === 'crop'" class="grid grid-cols-2 gap-2">
          <div>
            <label class="mb-1 block text-xs text-gray-500">X</label>
            <input v-model.number="cropForm.x" type="number" min="0" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">Y</label>
            <input v-model.number="cropForm.y" type="number" min="0" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">Width</label>
            <input v-model.number="cropForm.width" type="number" min="1" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">Height</label>
            <input v-model.number="cropForm.height" type="number" min="1" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
        </div>

        <!-- Resize -->
        <div v-if="activeAction === 'resize'" class="grid grid-cols-2 gap-2">
          <div>
            <label class="mb-1 block text-xs text-gray-500">Width</label>
            <input v-model.number="resizeForm.width" type="number" min="1" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">Height</label>
            <input v-model.number="resizeForm.height" type="number" min="1" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
        </div>

        <!-- Rotate -->
        <div v-if="activeAction === 'rotate'">
          <label class="mb-1 block text-xs text-gray-500">Angle: {{ rotateForm.angle }}&deg;</label>
          <input v-model.number="rotateForm.angle" type="range" min="0" max="360" step="1" class="w-full" />
        </div>

        <!-- Flip -->
        <div v-if="activeAction === 'flip'" class="space-y-2">
          <label class="flex items-center gap-2 text-sm text-gray-700">
            <input v-model="flipForm.direction" type="radio" value="horizontal" class="border-gray-300" />
            Horizontal
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700">
            <input v-model="flipForm.direction" type="radio" value="vertical" class="border-gray-300" />
            Vertical
          </label>
        </div>

        <!-- Text -->
        <div v-if="activeAction === 'text'" class="space-y-2">
          <div>
            <label class="mb-1 block text-xs text-gray-500">Text</label>
            <input v-model="textForm.text" type="text" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs text-gray-500">X</label>
              <input v-model.number="textForm.x" type="number" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-500">Y</label>
              <input v-model.number="textForm.y" type="number" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs text-gray-500">Size</label>
              <input v-model.number="textForm.size" type="number" min="1" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-500">Color</label>
              <input v-model="textForm.color" type="color" class="h-9 w-full cursor-pointer rounded border border-gray-300" />
            </div>
          </div>
        </div>

        <!-- Filter -->
        <div v-if="activeAction === 'filter'">
          <label class="mb-1 block text-xs text-gray-500">Filter</label>
          <select v-model="filterForm.filter" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            <option v-for="f in filterOptions" :key="f" :value="f" class="capitalize">{{ f }}</option>
          </select>
        </div>

        <!-- Watermark -->
        <div v-if="activeAction === 'watermark'">
          <label class="mb-1 block text-xs text-gray-500">Preset</label>
          <select v-model="watermarkForm.preset" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            <option v-for="p in watermarkPresets" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>

        <button
          class="mt-3 w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          :disabled="processing"
          @click="applyAction"
        >
          <i v-if="processing" class="pi pi-spin pi-spinner mr-1" />
          Apply {{ activeAction }}
        </button>
      </AppCard>
    </div>

    <!-- Right Panel: Image Preview -->
    <div class="lg:col-span-2">
      <AppCard>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-700">Preview</h3>
          <button
            v-if="resultUrl"
            class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
            @click="downloadResult"
          >
            <i class="pi pi-download mr-1" /> Download
          </button>
        </div>
        <div v-if="!imagePreviewUrl && !resultUrl" class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200 py-32 text-gray-400">
          <div class="text-center">
            <i class="pi pi-image mb-2 text-4xl" />
            <p class="text-sm">Upload an image to get started</p>
          </div>
        </div>
        <div v-else class="flex items-center justify-center rounded-lg bg-gray-100 p-4">
          <img
            :src="resultUrl || imagePreviewUrl || ''"
            alt="Image preview"
            class="max-h-[500px] max-w-full rounded object-contain"
          />
        </div>
      </AppCard>
    </div>
  </div>
</template>
