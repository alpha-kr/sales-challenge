import axios from 'axios'
import type { AxiosInstance, AxiosResponse, AxiosError } from 'axios'
import type { ApiErrorResponse, ApiSuccessResponse } from '@/types/api'

const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  // Required for the browser to send session cookies and the XSRF-TOKEN cookie
  withCredentials: true,
  withXSRFToken: true,
})

apiClient.interceptors.response.use(
  (response: AxiosResponse<ApiSuccessResponse<unknown>>) => {
    return response
  },
  async (error: AxiosError<ApiErrorResponse>) => {
    if (error.response?.status === 401) {
      const { useAuthStore } = await import('@/stores/auth')
      const auth = useAuthStore()
      auth.$patch({ user: null })

      if (window.location.pathname !== '/login') {
        const { default: router } = await import('@/router')
        router.push('/login')
      }
    }
    return Promise.reject(error)
  },
)

export default apiClient
