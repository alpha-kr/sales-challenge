<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'

interface Props {
  label?: string
  error?: string
  type?: string
}

defineProps<Props>()
defineOptions({ inheritAttrs: false })
const model = defineModel<string | number>()
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <Label v-if="label">{{ label }}</Label>
    <Input
      v-bind="$attrs"
      v-model="model"
      :type="type ?? 'text'"
      :class="cn(error ? 'border-destructive focus-visible:ring-destructive' : '')"
    />
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>
