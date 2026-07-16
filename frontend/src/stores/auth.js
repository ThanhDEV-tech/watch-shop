import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api, { TOKEN_KEY } from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem(TOKEN_KEY))
  const isAuthenticated = computed(() => Boolean(token.value && user.value))

  const setSession = (session) => {
    user.value = session.user
    token.value = session.token
    localStorage.setItem(TOKEN_KEY, session.token)
  }

  const clearSession = () => {
    user.value = null
    token.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  const login = async (credentials) => {
    const response = await api.post('/auth/login', credentials)
    setSession(response.data.data)

    return response.data.data
  }

  const register = async (payload) => {
    const response = await api.post('/auth/register', payload)
    setSession(response.data.data)

    return response.data.data
  }

  const logout = async () => {
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
    } finally {
      clearSession()
    }
  }

  const fetchMe = async () => {
    if (!token.value) return null

    try {
      const response = await api.get('/auth/me')
      user.value = response.data.data

      return user.value
    } catch {
      clearSession()
      return null
    }
  }

  const updateProfile = async (payload) => {
    const response = await api.put('/auth/profile', payload)
    user.value = response.data.data

    return response.data
  }

  const uploadAvatar = async (file) => {
    const formData = new FormData()
    formData.append('avatar', file)
    const response = await api.post('/auth/avatar', formData)
    user.value = response.data.data

    return response.data
  }

  const changePassword = async (payload) => {
    const response = await api.put('/auth/password', payload)

    return response.data
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchMe,
    updateProfile,
    uploadAvatar,
    changePassword,
  }
})
