import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'

/** Куда вести залогиненного пользователя вместо гостевой главной */
const authenticatedLanding: RouteLocationRaw = { name: 'profile' }

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: MainLayout,
      children: [
        {
          path: '',
          name: 'home',
          meta: { guestOnly: true },
          component: () => import('@/pages/HomePage.vue'),
        },
        {
          path: 'market',
          name: 'market',
          component: () => import('@/pages/MarketPage.vue'),
        },
        {
          path: 'market/:id(\\d+)',
          name: 'market-item',
          component: () => import('@/pages/MarketItemPage.vue'),
        },
        {
          path: 'cases',
          name: 'cases',
          component: () => import('@/pages/CasesPage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'cases/:id(\\d+)',
          name: 'case-detail',
          component: () => import('@/pages/CaseDetailPage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'upgrade',
          name: 'upgrade',
          component: () => import('@/pages/UpgradePage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'support',
          name: 'support',
          component: () => import('@/pages/SupportPage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/pages/ProfilePage.vue'),
          meta: { requiresAuth: true },
        },
      ],
    },
    {
      path: '/auth/steam-complete',
      name: 'auth.steam-complete',
      component: () => import('@/pages/AuthSteamCompletePage.vue'),
    },
    {
      path: '/auth/steam-error',
      name: 'auth.steam-error',
      component: () => import('@/pages/AuthErrorPage.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  if (to.meta.guestOnly) {
    const localToken = localStorage.getItem('auth_token')
    if (!localToken) {
      return
    }

    const auth = useAuthStore()
    if (!auth.token) {
      auth.$patch({ token: localToken })
    }
    if (!auth.user) {
      await auth.loadUser()
    }
    if (auth.user) {
      return authenticatedLanding
    }
    return
  }

  if (!to.meta.requiresAuth) {
    return
  }

  const localToken = localStorage.getItem('auth_token')
  if (!localToken) {
    return { name: 'home' }
  }

  const auth = useAuthStore()

  // If Pinia token is out of sync with localStorage (e.g. page just loaded and
  // auth store was re-created before bootstrap finished), sync it manually so
  // loadUser() doesn't bail out on the `if (!token.value) return` check.
  if (!auth.token) {
    auth.$patch({ token: localToken })
  }

  if (!auth.user) {
    await auth.loadUser()
  }

  if (!auth.user) {
    return { name: 'home' }
  }
})

export default router
