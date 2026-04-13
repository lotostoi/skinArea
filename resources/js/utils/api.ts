import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  if (config.skipAuth) {
    if (typeof config.headers?.delete === 'function') {
      config.headers.delete('Authorization')
    } else {
      delete (config.headers as Record<string, unknown> | undefined)?.Authorization
    }
    return config
  }
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = String(error.config?.url ?? '')
      const isSteamExchange = url.includes('auth/steam/exchange')
      if (!isSteamExchange) {
        localStorage.removeItem('auth_token')
        window.location.href = '/'
      }
    }
    return Promise.reject(error)
  },
)

export default api
