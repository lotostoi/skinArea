/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** В dev: тот же хост, что APP_URL (Laravel), чтобы Steam OpenID и сессия были на одном origin. */
  readonly VITE_BACKEND_URL?: string
}

declare module 'axios' {
  interface AxiosRequestConfig {
    /** Не подставлять Bearer (например POST /auth/steam/exchange со старым токеном) */
    skipAuth?: boolean
  }
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}
