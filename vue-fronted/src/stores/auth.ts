import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/AuthService'
import type { LoginCredentials, AuthUser } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const isAuthenticated = computed(() => user.value !== null)
  const isChecked = ref(false)

  async function checkAuth(): Promise<void> {
    if (isChecked.value) return
    try {
      user.value = await authService.getCurrentUser()
    } catch {
      user.value = null
    } finally {
      isChecked.value = true
    }
  }

  async function login(credentials: LoginCredentials): Promise<void> {
    await authService.login(credentials)
    user.value = await authService.getCurrentUser()
    isChecked.value = true
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      user.value = null
      isChecked.value = false
    }
  }

  return { user, isAuthenticated, isChecked, checkAuth, login, logout }
})
