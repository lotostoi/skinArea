import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'

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
          component: () => import('@/pages/HomePage.vue'),
        },
        {
          path: 'market',
          name: 'market',
          component: () => import('@/pages/MarketPage.vue'),
        },
        {
          path: 'cases',
          name: 'cases',
          component: () => import('@/pages/CasesPage.vue'),
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
  if (!to.meta.requiresAuth) {
    return
  }
  const token = localStorage.getItem('auth_token')
  if (!token) {
    return { name: 'home' }
  }
  const auth = useAuthStore()
  if (!auth.user) {
    await auth.loadUser()
  }
  if (!auth.user) {
    return { name: 'home' }
  }
})

export default router
