import { ref, reactive } from 'vue'
import type { AxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'
import { useUiStore } from '@/stores/ui'

export function useValidationErrors() {
  const fieldErrors = ref<Record<string, string[]>>({})

  const errorDialog = reactive({
    open: false,
    code: '',
    message: '',
    details: {} as Record<string, string[]>,
  })

  function handleApiError(error: unknown): void {
    const ui = useUiStore()
    const axiosError = error as AxiosError<ApiErrorResponse>

    if (!axiosError.response) {
      ui.toast.error('Network error. Please check your connection.')
      return
    }

    const { data } = axiosError.response

    if (data?.error?.code && data.error.details) {
      fieldErrors.value = data.error.details
      errorDialog.code = data.error.code
      errorDialog.message = data.error.message
      errorDialog.details = data.error.details
      errorDialog.open = true
      return
    }

    const message = data?.error?.message ?? 'An unexpected error occurred.'
    ui.toast.error(message)
  }

  function clearErrors(): void {
    fieldErrors.value = {}
  }

  function closeErrorDialog(): void {
    errorDialog.open = false
  }

  function getFieldError(field: string): string | undefined {
    return fieldErrors.value[field]?.[0]
  }

  return { fieldErrors, handleApiError, clearErrors, getFieldError, errorDialog, closeErrorDialog }
}
