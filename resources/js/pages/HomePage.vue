<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'

const auth = useAuthStore()
</script>

<template>
  <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <h1 class="text-4xl font-bold mb-4">
      <span class="text-primary">Skins</span><span class="text-text-primary">Arena</span>
    </h1>
    <p class="text-text-secondary text-lg mb-8 max-w-xl">
      P2P-маркетплейс скинов CS2. Покупай, продавай, открывай кейсы и делай апгрейды.
    </p>

    <div class="flex gap-4">
      <router-link to="/market">
        <AppButton variant="secondary" size="lg">Маркетплейс</AppButton>
      </router-link>
      <router-link to="/cases">
        <AppButton variant="primary" size="lg">Открыть кейсы</AppButton>
      </router-link>
    </div>

    <div v-if="!auth.isAuthenticated" class="mt-12">
      <button
        class="flex items-center gap-3 px-8 py-3 bg-[#1b2838] text-white font-semibold rounded-lg hover:bg-[#2a475e] transition-colors border border-[#2a475e]"
        @click="auth.steamLogin"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white">
          <path d="M12 2C6.477 2 2 6.477 2 12c0 5.013 3.693 9.153 8.505 9.876V15.65c-.963-.46-1.673-1.39-1.76-2.487l-2.88-1.34a.75.75 0 01-.365-.996.75.75 0 01.996-.365l2.619 1.219A2.998 2.998 0 0112 10c1.657 0 3 1.343 3 3 0 1.316-.853 2.431-2.035 2.83v4.046C17.822 19.273 22 16.065 22 12c0-5.523-4.477-10-10-10z"/>
        </svg>
        Войти через Steam
      </button>
    </div>
  </div>
</template>
