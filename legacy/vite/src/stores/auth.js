import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const isAuthenticated = ref(!!token.value)

  const login = async (email, password) => {
    try {
      const response = await axios.post('http://localhost:8000/api/auth/login', {
        email,
        password,
      }, {
        headers: {
          'Content-Type': 'application/json',
        }
      })
      
      const { accessToken, user: userData } = response.data.data
      token.value = accessToken
      user.value = userData
      isAuthenticated.value = true
      localStorage.setItem('token', accessToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
      
      return true
    } catch (error) {
      console.error('Login failed:', error.response?.data || error.message)
      return false
    }
  }

  const logout = () => {
    token.value = null
    user.value = null
    isAuthenticated.value = false
    localStorage.removeItem('token')
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
  }
})
