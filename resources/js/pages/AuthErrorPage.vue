<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const reasonMessages: Record<string, string> = {
  steam_denied: 'Авторизация через Steam была отклонена.',
  banned: 'Ваш аккаунт заблокирован.',
}

const reason = computed(() => {
  const r = route.query.reason as string | undefined
  return r ? (reasonMessages[r] ?? 'Произошла неизвестная ошибка.') : 'Произошла ошибка при авторизации.'
})

const banReason = computed(() => route.query.reason === 'banned' ? (route.query.reason as string) : null)
</script>

<template>
  <div class="min-h-screen bg-body flex items-center justify-center">
    <div class="bg-surface border border-border rounded-xl p-8 max-w-md w-full text-center">
      <div class="text-danger text-lg font-semibold mb-4">Ошибка авторизации</div>
      <p class="text-text-secondary mb-2">{{ reason }}</p>
      <p v-if="banReason" class="text-text-muted text-sm mb-6">Причина: {{ banReason }}</p>
      <router-link
        to="/"
        class="inline-block px-6 py-2 bg-primary text-text-on-primary font-semibold rounded-md hover:bg-primary-hover transition-colors"
      >
        На главную
      </router-link>
    </div>
  </div>
</template>
