<script setup lang="ts">
import InputText from 'primevue/inputtext'
import InputIcon from 'primevue/inputicon'
import IconField from 'primevue/iconfield'

defineProps<{
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <IconField>
    <InputIcon class="pi pi-search" />
    <InputText
      :model-value="modelValue"
      :placeholder="placeholder || 'Search...'"
      class="w-full"
      @update:model-value="emit('update:modelValue', $event as string)"
    />
  </IconField>
</template>
