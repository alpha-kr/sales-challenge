<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

interface Props {
  title: string
  open: boolean
}

defineProps<Props>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <Dialog :open="open" @update:open="(val) => { if (!val) emit('close') }">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
      </DialogHeader>
      <slot />
    </DialogContent>
  </Dialog>
</template>
