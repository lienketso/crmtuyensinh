import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('access_token') || null)

    const isAuthenticated = computed(() => !!token.value && !!user.value)

    // Hàm khởi tạo
    function initialize() {
        const savedToken = localStorage.getItem('access_token')
        const savedUser = localStorage.getItem('user')

        if (savedToken) {
            token.value = savedToken
            user.value = savedUser ? JSON.parse(savedUser) : null
        }
    }

    async function login(credentials) {
        try {
            console.log('Login attempt:', credentials.email)

            const response = await api.post('/login', credentials)

            if (response.data.access_token) {
                // Lưu token và user
                token.value = response.data.access_token
                user.value = response.data.user

                // Lưu vào localStorage
                localStorage.setItem('access_token', token.value)
                localStorage.setItem('user', JSON.stringify(user.value))

                console.log('Login successful, redirecting to dashboard')

                // Redirect đến dashboard
                router.push({ name: 'dashboard' })

                return response
            } else {
                throw new Error('Không nhận được token từ server')
            }

        } catch (error) {
            console.error('Auth store login error:', error)
            throw error
        }
    }

    function logout() {
        token.value = null
        user.value = null
        localStorage.removeItem('access_token')
        localStorage.removeItem('user')

        // Redirect đến login
        router.push({ name: 'login-admin' })
    }

    function setUser(nextUser) {
        user.value = nextUser
        localStorage.setItem('user', JSON.stringify(user.value))
    }

    return {
        user,
        token,
        isAuthenticated,
        initialize,
        login,
        logout,
        setUser
    }
})
