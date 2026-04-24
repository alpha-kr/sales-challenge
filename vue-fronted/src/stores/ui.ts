import { defineStore } from 'pinia'
import { toast } from 'vue-sonner'

export const useUiStore = defineStore('ui', () => {
  const toastHelper = {
    success: (message: string) => toast.success(message),
    error: (message: string) => toast.error(message),
    warning: (message: string) => toast.warning(message),
    info: (message: string) => toast.info(message),
  }

  return { toast: toastHelper }
})
