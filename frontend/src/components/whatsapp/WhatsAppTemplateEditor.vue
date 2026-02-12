<script setup lang="ts">
import { ref, computed } from 'vue'
import type { CreateTemplateRequest, WhatsAppTemplateButton, WhatsAppTemplateCategory, WhatsAppHeaderType } from '@/types/whatsapp-marketing'
import WhatsAppTemplatePreview from './WhatsAppTemplatePreview.vue'

const props = defineProps<{
  phoneNumberId: string
  saving?: boolean
}>()

const emit = defineEmits<{
  save: [data: CreateTemplateRequest]
}>()

const form = ref({
  name: '',
  language: 'en',
  category: 'marketing' as WhatsAppTemplateCategory,
  header_type: 'none' as WhatsAppHeaderType,
  header_content: '',
  body_text: '',
  footer_text: '',
  buttons: [] as WhatsAppTemplateButton[],
  sample_values: [] as string[],
})

const canSave = computed(() => form.value.name.length > 0 && form.value.body_text.length > 0)

function addButton() {
  if (form.value.buttons.length >= 3) return
  form.value.buttons.push({ type: 'QUICK_REPLY', text: '' })
}

function removeButton(index: number) {
  form.value.buttons.splice(index, 1)
}

function insertPlaceholder() {
  const nextNum = (form.value.body_text.match(/\{\{\d+\}\}/g) || []).length + 1
  form.value.body_text += `{{${nextNum}}}`
  form.value.sample_values.push('')
}

function handleSave() {
  if (!canSave.value) return
  emit('save', {
    whatsapp_phone_number_id: props.phoneNumberId,
    name: form.value.name,
    language: form.value.language,
    category: form.value.category,
    header_type: form.value.header_type,
    header_content: form.value.header_content || undefined,
    body_text: form.value.body_text,
    footer_text: form.value.footer_text || undefined,
    buttons: form.value.buttons.length > 0 ? form.value.buttons : undefined,
    sample_values: form.value.sample_values.length > 0 ? form.value.sample_values : undefined,
  })
}
</script>

<template>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Editor -->
    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Template Name *</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
            placeholder="order_confirmation"
          />
          <p class="mt-0.5 text-xs text-gray-400">Lowercase, underscores only</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Language</label>
          <select v-model="form.language" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="en">English</option>
            <option value="hi">Hindi</option>
            <option value="es">Spanish</option>
            <option value="pt_BR">Portuguese (BR)</option>
            <option value="ar">Arabic</option>
          </select>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Category *</label>
        <select v-model="form.category" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="marketing">Marketing</option>
          <option value="utility">Utility</option>
          <option value="authentication">Authentication</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Header</label>
        <select v-model="form.header_type" class="mb-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="none">None</option>
          <option value="text">Text</option>
          <option value="image">Image</option>
          <option value="video">Video</option>
          <option value="document">Document</option>
        </select>
        <input
          v-if="form.header_type === 'text'"
          v-model="form.header_content"
          type="text"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
          placeholder="Header text"
        />
      </div>

      <div>
        <div class="mb-1 flex items-center justify-between">
          <label class="text-sm font-medium text-gray-700">Body Text *</label>
          <button type="button" class="text-xs text-green-600 hover:underline" @click="insertPlaceholder">
            + Variable
          </button>
        </div>
        <textarea
          v-model="form.body_text"
          rows="5"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
          placeholder="Hi {{1}}, your order {{2}} is confirmed."
        />
        <p class="mt-0.5 text-xs text-gray-400">{{ form.body_text.length }} / 1024 chars</p>
      </div>

      <!-- Sample values for placeholders -->
      <div v-if="form.sample_values.length > 0">
        <label class="mb-1 block text-sm font-medium text-gray-700">Sample Values (for Meta approval)</label>
        <div v-for="(_, i) in form.sample_values" :key="i" class="mb-1 flex items-center gap-2">
          <span class="text-xs text-gray-500" v-text="'{{' + (i + 1) + '}}'"></span>
          <input
            v-model="form.sample_values[i]"
            type="text"
            class="flex-1 rounded-lg border border-gray-300 px-2 py-1 text-sm"
            placeholder="Sample value"
          />
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Footer</label>
        <input
          v-model="form.footer_text"
          type="text"
          maxlength="60"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
          placeholder="Optional footer (max 60 chars)"
        />
      </div>

      <!-- Buttons -->
      <div>
        <div class="mb-1 flex items-center justify-between">
          <label class="text-sm font-medium text-gray-700">Buttons</label>
          <button
            v-if="form.buttons.length < 3"
            type="button"
            class="text-xs text-green-600 hover:underline"
            @click="addButton"
          >
            + Add Button
          </button>
        </div>
        <div v-for="(btn, i) in form.buttons" :key="i" class="mb-2 flex items-center gap-2">
          <select v-model="btn.type" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
            <option value="QUICK_REPLY">Quick Reply</option>
            <option value="URL">URL</option>
            <option value="PHONE_NUMBER">Phone</option>
          </select>
          <input v-model="btn.text" type="text" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm" placeholder="Button text" />
          <input v-if="btn.type === 'URL'" v-model="btn.url" type="url" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm" placeholder="https://..." />
          <input v-if="btn.type === 'PHONE_NUMBER'" v-model="btn.phone_number" type="tel" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm" placeholder="+91..." />
          <button type="button" class="text-gray-400 hover:text-red-500" @click="removeButton(i)">
            <i class="pi pi-times text-sm" />
          </button>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="button"
          :disabled="!canSave || saving"
          class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
          @click="handleSave"
        >
          <i v-if="saving" class="pi pi-spin pi-spinner mr-1" />
          Save Template
        </button>
      </div>
    </div>

    <!-- Preview -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-gray-900">Preview</h3>
      <WhatsAppTemplatePreview
        :template="null"
        :header-type="form.header_type"
        :header-content="form.header_content"
        :body-text="form.body_text"
        :footer-text="form.footer_text"
        :buttons="form.buttons"
      />
    </div>
  </div>
</template>
