<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const error = ref<string | null>(null)

onMounted(async () => {
  const code = route.query.code as string | undefined

  if (!code) {
    error.value = 'Код авторизации отсутствует'
    return
  }

  try {
    await auth.exchangeCode(code)
    await router.replace({ name: 'profile' })
  } catch {
    error.value = 'Не удалось завершить авторизацию. Попробуйте ещё раз.'
  }
})
</script>

<template>
  <div class="min-h-screen bg-body flex items-center justify-center">
    <div class="bg-surface border border-border rounded-xl p-8 max-w-md w-full text-center">
      <template v-if="error">
        <div class="text-danger text-lg font-semibold mb-4">Ошибка</div>
        <p class="text-text-secondary mb-6">{{ error }}</p>
        <router-link
          to="/"
          class="inline-block px-6 py-2 bg-primary text-text-on-primary font-semibold rounded-md hover:bg-primary-hover transition-colors"
        >
          На главную
        </router-link>
      </template>

      <template v-else>
        <div class="flex justify-center mb-4">
          <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
        </div>
        <p class="text-text-secondary">Выполняется вход...</p>
      </template>
    </div>
  </div>
</template>
