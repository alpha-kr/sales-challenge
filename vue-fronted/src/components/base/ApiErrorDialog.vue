<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

defineProps<{
  errorDialog: {
    open: boolean
    code: string
    message: string
    details: Record<string, string[]>
  }
}>()

const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <Dialog :open="errorDialog.open" @update:open="(val: boolean) => { if (!val) emit('close') }">
    <DialogContent class="max-w-sm">
      <DialogHeader>
        <DialogTitle class="text-destructive">ERROR: {{ errorDialog.code }}</DialogTitle>
        <DialogDescription>{{ errorDialog.message }}</DialogDescription>
      </DialogHeader>

      <ul v-if="Object.keys(errorDialog.details).length > 0" class="space-y-1.5 text-sm">
        <li
          v-for="(errors, field) in errorDialog.details"
          :key="field"
          class="flex gap-1.5"
        >
          <span class="font-medium capitalize shrink-0">
            {{ String(field).replace(/_/g, ' ') }}:
          </span>
          <span class="text-destructive">{{ errors[0] }}</span>
        </li>
      </ul>

      <DialogFooter>
        <Button class="w-full" @click="emit('close')">OK</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
