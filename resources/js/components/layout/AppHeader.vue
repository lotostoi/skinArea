<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useBalanceStore } from '@/stores/balance'

const auth = useAuthStore()
const balance = useBalanceStore()

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
          <div class="flex items-center gap-2 px-3 py-1.5 bg-surface rounded-md border border-border">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a5.95 5.95 0 01-.488-.521H10a1 1 0 100-2H7.938a7.468 7.468 0 010-1H10a1 1 0 100-2H8.248c.14-.18.3-.373.488-.521z" />
            </svg>
            <span class="text-sm font-bold text-primary">{{ balance.mainBalance }} ₽</span>
          </div>

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
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3l-.5 3H13v6.95c5.05-.5 9-4.76 9-9.95 0-5.52-4.48-10-10-10z"/>
          </svg>
          Войти через Steam
        </button>
      </div>
    </div>
  </header>
</template>
