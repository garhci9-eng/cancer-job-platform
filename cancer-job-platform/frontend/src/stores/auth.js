// frontend/src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isLoggedIn = computed(() => !!token.value)

  async function login(email, password) {
    const { data } = await api.post('/auth/login', { email, password })
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
  }

  async function fetchMe() {
    if (!token.value) return
    const { data } = await api.get('/auth/me')
    user.value = data.user
  }

  async function logout() {
    await api.post('/auth/logout').catch(() => {})
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
  }

  return { user, token, isLoggedIn, login, register, fetchMe, logout }
})
