<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
</script>

<template>
  <RouterView />
  <Teleport to="body">
    <Toaster rich-colors />
  </Teleport>
</template>
