<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  LayoutDashboard,
  BarChart3,
  PlusCircle,
  Users,
  Package,
  Settings,
  LogOut,
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()

const navLinks = [
  { name: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { name: 'Sales', to: '/sales', icon: BarChart3 },
  { name: 'New Sale', to: '/sales/new', icon: PlusCircle },
  { name: 'Clients', to: '/clients', icon: Users },
  { name: 'Products', to: '/products', icon: Package },
  { name: 'Services', to: '/services', icon: Settings },
]

async function handleLogout(): Promise<void> {
  await auth.logout()
  ui.toast.success('Logged out successfully')
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-muted/40 flex">
    <!-- Sidebar -->
    <aside class="w-56 bg-background border-r flex flex-col shrink-0">
      <div class="px-6 py-5">
        <h1 class="text-xl font-bold tracking-tight">SalesApp</h1>
        <p class="text-xs text-muted-foreground mt-0.5">Sales Management</p>
      </div>

      <Separator />

      <nav class="flex-1 px-3 py-4 space-y-1">
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors"
          :class="
            $route.path === link.to
              ? 'bg-primary text-primary-foreground'
              : 'text-muted-foreground hover:bg-muted hover:text-foreground'
          "
        >
          <component :is="link.icon" class="size-4 shrink-0" />
          {{ link.name }}
        </RouterLink>
      </nav>

      <Separator />

      <div class="px-3 py-4">
        <Button variant="ghost" size="sm" class="w-full justify-start gap-3 text-muted-foreground" @click="handleLogout">
          <LogOut class="size-4" />
          Sign out
        </Button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>
  </div>
</template>
