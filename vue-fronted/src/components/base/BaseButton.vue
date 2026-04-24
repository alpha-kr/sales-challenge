<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Loader2 } from 'lucide-vue-next'

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}

const {
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  type = 'button',
} = defineProps<Props>()

const shadcnVariant = computed(() => {
  const map = { primary: 'default', secondary: 'outline', danger: 'destructive', ghost: 'ghost' } as const
  return map[variant]
})

const shadcnSize = computed(() => {
  const map = { sm: 'sm', md: 'default', lg: 'lg' } as const
  return map[size]
})
</script>

<template>
  <Button :type="type" :variant="shadcnVariant" :size="shadcnSize" :disabled="disabled || loading">
    <Loader2 v-if="loading" class="animate-spin" />
    <slot />
  </Button>
</template>
