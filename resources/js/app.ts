import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import App from '@/App.vue'
import { useAuthStore } from '@/stores/auth'

async function bootstrap(): Promise<void> {
  const app = createApp(App)
  const pinia = createPinia()
  app.use(pinia)

  const auth = useAuthStore()

  // Load user BEFORE registering the router so that when the router fires
  // its initial navigation guard, auth.user is already populated.
  // This prevents a race condition where both bootstrap AND the guard call
  // loadUser() concurrently, which can lead to auth.user being null due to
  // one of the parallel calls hitting logout() on error.
  if (auth.token && !auth.user) {
    await auth.loadUser()
  }

  app.use(router)
  await router.isReady()
  app.mount('#app')
}

void bootstrap()
