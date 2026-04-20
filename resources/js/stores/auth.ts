import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import type { User } from '@/types/models'
import type { AuthExchangeResponse } from '@/types/api'
import { useBalanceStore } from '@/stores/balance'
import { SESSION_POST_STEAM_WELCOME_MODAL } from '@/utils/constants'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)

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
    try {
      sessionStorage.setItem(SESSION_POST_STEAM_WELCOME_MODAL, '1')
    } catch {
      /* приватный режим / квота — вход не блокируем */
    }
  }

  async function loadUser(): Promise<void> {
    // Sync token from localStorage if Pinia state is empty (e.g. after a hard
    // page reload where Pinia was re-created before bootstrap finished).
    if (!token.value) {
      const stored = localStorage.getItem('auth_token')
      if (!stored) return
      token.value = stored
    }
    loading.value = true
    try {
      const { data } = await api.get<{ data: User }>('/user')
      user.value = data.data
      const balance = useBalanceStore()
      await balance.fetchBalances(data.data.balances)
    } catch {
      logout()
    } finally {
      loading.value = false
    }
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

  function firstApiErrorPart(
    e: unknown,
    field: 'email' | 'trade_url',
    fallback: string,
  ): string {
    const ax = e as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
    const errs = ax.response?.data?.errors
    const fromField = errs?.[field]?.[0]
    return (typeof fromField === 'string' ? fromField : null) ?? ax.response?.data?.message ?? fallback
  }

  async function patchWelcomeProfile(payload: {
    email?: string | null
    tradeUrl?: string | null
  }): Promise<void> {
    const failures: string[] = []

    if (payload.email !== undefined) {
      const nextEmail =
        payload.email === null || payload.email === ''
          ? null
          : String(payload.email).trim()
      try {
        await updateEmail(nextEmail === '' ? null : nextEmail)
      } catch (e: unknown) {
        failures.push(firstApiErrorPart(e, 'email', 'Не удалось сохранить email.'))
      }
    }

    const tradeRaw = payload.tradeUrl
    if (tradeRaw !== undefined && tradeRaw !== null && String(tradeRaw).trim() !== '') {
      try {
        await updateTradeUrl(String(tradeRaw).trim())
      } catch (e: unknown) {
        failures.push(firstApiErrorPart(e, 'trade_url', 'Не удалось сохранить trade URL.'))
      }
    }

    if (failures.length > 0) {
      const err = new Error(failures.join(' ')) as Error & {
        response?: { data: { message: string; errors: Record<string, string[]> } }
      }
      err.response = { data: { message: failures.join(' '), errors: {} } }
      throw err
    }
  }

  function logout(): void {
    if (token.value) {
      api.post('/auth/logout', {}).catch(() => {})
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    sessionStorage.removeItem(SESSION_POST_STEAM_WELCOME_MODAL)
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
    patchWelcomeProfile,
    logout,
    steamLogin,
  }
})
