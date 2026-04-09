import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'

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
        },
        {
          path: 'upgrade',
          name: 'upgrade',
          component: () => import('@/pages/UpgradePage.vue'),
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

router.beforeEach((to) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      return { name: 'home' }
    }
  }
})

export default router
