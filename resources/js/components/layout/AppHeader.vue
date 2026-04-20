<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useBalanceStore } from '@/stores/balance'
import { useDepositModalStore } from '@/stores/depositModal'

const auth = useAuthStore()
const balance = useBalanceStore()
const depositModal = useDepositModalStore()

const navItems = computed(() => {
  const items = [{ to: '/market', label: 'Маркет' }]
  if (!auth.isAuthenticated) {
    return items
  }
  return [
    ...items,
    { to: '/cases', label: 'Кейсы' },
    { to: '/upgrade', label: 'Апгрейд' },
    { to: '/support', label: 'Поддержка' },
  ]
})
</script>

<template>
  <header class="sticky top-0 z-50 bg-sidebar border-b border-border">
    <div class="max-w-[1400px] mx-auto flex items-center justify-between h-16 px-6">
      <div class="flex items-center gap-8">
        <router-link to="/" class="text-xl font-bold text-primary">
          SkinsArena
        </router-link>

        <nav class="flex items-center gap-1">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="px-4 py-2 text-sm font-medium text-text-secondary rounded-md transition-colors hover:text-text-primary hover:bg-surface-hover"
            active-class="!text-text-primary !bg-surface"
          >
            {{ item.label }}
          </router-link>
        </nav>
      </div>

      <div class="flex items-center gap-4">
        <template v-if="auth.isAuthenticated && auth.user">
          <button
            type="button"
            class="flex items-center gap-2 px-3 py-1.5 bg-surface rounded-md border border-border transition-colors hover:border-primary hover:bg-surface-hover"
            title="Пополнить баланс"
            @click="depositModal.open()"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a5.95 5.95 0 01-.488-.521H10a1 1 0 100-2H7.938a7.468 7.468 0 010-1H10a1 1 0 100-2H8.248c.14-.18.3-.373.488-.521z" />
            </svg>
            <span class="text-sm font-bold text-primary">{{ balance.mainBalance }} ₽</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
          </button>

          <router-link
            to="/profile"
            class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors hover:bg-surface-hover"
          >
            <img
              v-if="auth.user.avatar_url"
              :src="auth.user.avatar_url"
              :alt="auth.user.username"
              class="w-8 h-8 rounded-full"
            />
            <div
              v-else
              class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-xs font-bold text-white"
            >
              {{ auth.user.username.charAt(0).toUpperCase() }}
            </div>
            <span class="text-sm text-text-primary hidden sm:inline">{{ auth.user.username }}</span>
          </router-link>

          <button
            class="text-xs text-text-muted hover:text-danger transition-colors"
            @click="auth.logout"
          >
            Выйти
          </button>
        </template>

        <button
          v-else
          class="flex items-center gap-2 px-4 py-2 bg-primary text-text-on-primary font-semibold text-sm rounded-md transition-colors hover:bg-primary-hover"
          @click="auth.steamLogin"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5 shrink-0"
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.605 0 11.979 0zM7.54 18.21l-1.473-.61c.262.543.714.999 1.314 1.25 1.297.539 2.793-.076 3.332-1.375.263-.63.264-1.319.005-1.949s-.75-1.121-1.377-1.383c-.624-.26-1.29-.249-1.878-.03l1.523.63c.956.4 1.409 1.5 1.009 2.455-.397.957-1.497 1.41-2.454 1.012H7.54zm11.415-9.303c0-1.662-1.353-3.015-3.015-3.015-1.665 0-3.015 1.353-3.015 3.015 0 1.665 1.35 3.015 3.015 3.015 1.663 0 3.015-1.35 3.015-3.015zm-5.273-.005c0-1.252 1.013-2.266 2.265-2.266 1.249 0 2.266 1.014 2.266 2.266 0 1.251-1.017 2.265-2.266 2.265-1.253 0-2.265-1.014-2.265-2.265z"
            />
          </svg>
          Войти через Steam
        </button>
      </div>
    </div>
  </header>
</template>
