<script setup lang="ts">
import Button from 'primevue/button'

defineProps<{
  canSubmit?: boolean
  canSchedule?: boolean
  canPublish?: boolean
  saving?: boolean
}>()

const emit = defineEmits<{
  save: []
  submit: []
  schedule: []
  publish: []
}>()
</script>

<template>
  <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4">
    <Button
      label="Save Draft"
      icon="pi pi-save"
      severity="secondary"
      :loading="saving"
      @click="emit('save')"
    />
    <Button
      v-if="canSubmit"
      label="Submit for Approval"
      icon="pi pi-send"
      severity="info"
      :loading="saving"
      @click="emit('submit')"
    />
    <Button
      v-if="canSchedule"
      label="Schedule"
      icon="pi pi-clock"
      severity="warn"
      :loading="saving"
      @click="emit('schedule')"
    />
    <Button
      v-if="canPublish"
      label="Publish Now"
      icon="pi pi-upload"
      :loading="saving"
      @click="emit('publish')"
    />
  </div>
</template>
