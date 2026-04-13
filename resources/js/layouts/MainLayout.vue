<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppMessageDialog from '@/components/ui/AppMessageDialog.vue'
import PostSteamWelcomeModal from '@/components/layout/PostSteamWelcomeModal.vue'
import { useAuthStore } from '@/stores/auth'
import {
  SESSION_POST_STEAM_WELCOME_MODAL,
  welcomeModalSkippedStorageKey,
} from '@/utils/constants'

const auth = useAuthStore()
const showPostSteamWelcome = ref(false)

function syncPostSteamWelcome(): void {
  if (!auth.isAuthenticated || !auth.user) {
    showPostSteamWelcome.value = false
    return
  }
  if (localStorage.getItem(welcomeModalSkippedStorageKey(auth.user.id))) {
    showPostSteamWelcome.value = false
    return
  }
  showPostSteamWelcome.value = sessionStorage.getItem(SESSION_POST_STEAM_WELCOME_MODAL) === '1'
}

onMounted(() => {
  syncPostSteamWelcome()
})

watch(
  () => [auth.isAuthenticated, auth.user?.id] as const,
  () => {
    syncPostSteamWelcome()
  },
)
</script>

<template>
  <div class="min-h-screen bg-body">
    <AppHeader />
    <main class="max-w-[1400px] mx-auto px-6 py-6">
      <router-view />
    </main>
    <PostSteamWelcomeModal v-if="showPostSteamWelcome" @close="syncPostSteamWelcome" />
    <AppMessageDialog />
  </div>
</template>
