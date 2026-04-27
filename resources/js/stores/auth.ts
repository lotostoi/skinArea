import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import type { User } from '@/types/models'
import type { AuthExchangeResponse } from '@/types/api'
import { useBalanceStore } from '@/stores/balance'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  let loadUserPromise: Promise<void> | null = null

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  async function exchangeCode(code: string): Promise<void> {
    const { data } = await api.post<AuthExchangeResponse>(
      '/auth/steam/exchange',
      { code },
      { skipAuth: true },
    )
    token.value = data.data.token
    user.value = data.data.user
    localStorage.setItem('auth_token', data.data.token)
    const balance = useBalanceStore()
    await balance.fetchBalances(data.data.user.balances)
  }

  async function loadUser(): Promise<void> {
    if (loadUserPromise) {
      return loadUserPromise
    }

    // Sync token from localStorage if Pinia state is empty (e.g. after a hard
    // page reload where Pinia was re-created before bootstrap finished).
    if (!token.value) {
      const stored = localStorage.getItem('auth_token')
      if (!stored) return
      token.value = stored
    }
    loadUserPromise = (async () => {
      loading.value = true
      try {
        const { data } = await api.get<{ data: User }>('/user')
        user.value = data.data
        const balance = useBalanceStore()
        await balance.fetchBalances(data.data.balances)
      } catch {
        logout({ server: false })
      } finally {
        loading.value = false
        loadUserPromise = null
      }
    })()

    return loadUserPromise
  }

  async function updateTradeUrl(tradeUrl: string): Promise<void> {
    const { data } = await api.patch<{ data: User }>('/user/trade-url', { trade_url: tradeUrl })
    user.value = data.data
    const balance = useBalanceStore()
    await balance.fetchBalances(data.data.balances ?? [])
  }

  async function updateEmail(email: string | null): Promise<void> {
    const { data } = await api.patch<{ data: User }>('/user/email', { email })
    user.value = data.data
    const balance = useBalanceStore()
    await balance.fetchBalances(data.data.balances ?? [])
  }

  async function resendEmailVerification(): Promise<string> {
    const { data } = await api.post<{ message: string }>('/user/email/verification-notification')
    return data.message
  }

  function logout(options?: { server?: boolean; redirectToHome?: boolean }): void {
    const shouldCallServer = options?.server ?? true
    const shouldRedirectToHome = options?.redirectToHome ?? false

    if (token.value && shouldCallServer) {
      api.post('/auth/logout', {}).catch(() => {})
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')

    if (shouldRedirectToHome && window.location.pathname !== '/') {
      window.location.assign('/')
    }
  }

  function steamLogin(): void {
    const backend = import.meta.env.VITE_BACKEND_URL?.trim()
    if (backend) {
      const base = backend.replace(/\/$/, '')
      window.location.assign(`${base}/auth/steam`)
      return
    }
    window.location.assign('/auth/steam')
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    exchangeCode,
    loadUser,
    updateTradeUrl,
    updateEmail,
    resendEmailVerification,
    logout,
    steamLogin,
  }
})
