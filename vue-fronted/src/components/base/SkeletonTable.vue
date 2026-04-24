<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'

interface Props {
  rows?: number
  cols?: number
}

const { rows = 5, cols = 4 } = defineProps<Props>()
</script>

<template>
  <div class="p-4 space-y-3">
    <Skeleton class="h-10 w-full" />
    <div v-for="r in rows" :key="r" class="flex gap-2">
      <Skeleton v-for="c in cols" :key="c" class="h-9 flex-1" />
    </div>
  </div>
</template>
