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

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  async function exchangeCode(code: string): Promise<void> {
    const { data } = await api.post<AuthExchangeResponse>('/auth/steam/exchange', { code })
    token.value = data.data.token
    user.value = data.data.user
    localStorage.setItem('auth_token', data.data.token)
    const balance = useBalanceStore()
    await balance.fetchBalances(data.data.user.balances)
  }

  async function loadUser(): Promise<void> {
    if (!token.value) return
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

  function logout(): void {
    if (token.value) {
      api.post('/auth/steam/exchange', {}).catch(() => {})
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  function steamLogin(): void {
    window.location.href = '/auth/steam'
  }

  return { user, token, loading, isAuthenticated, exchangeCode, loadUser, logout, steamLogin }
})
