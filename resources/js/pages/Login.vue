<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">AI Tuyển Sinh</h1>
                <p class="text-gray-600">Đăng nhập vào hệ thống</p>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-6">
                <!-- Hiển thị lỗi -->
                <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ errorMessage }}
                </div>

                <!-- Email input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                        placeholder="admin@example.com"
                        :disabled="loading"
                    />
                </div>

                <!-- Password input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Mật khẩu
                    </label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                        placeholder="••••••••"
                        :disabled="loading"
                    />
                </div>

                <!-- Submit button -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
          <span v-if="loading">
            <svg class="animate-spin h-5 w-5 mr-2 inline-block text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Đang đăng nhập...
          </span>
                    <span v-else>Đăng nhập</span>
                </button>

                <!-- Demo credentials (optional) -->
                <div class="mt-4 text-center text-sm text-gray-500">
                    <p>Demo credentials:</p>
                    <p class="font-mono mt-1">admin@example.com / password123</p>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Form data
const email = ref('admin@example.com') // Pre-fill for testing
const password = ref('password123') // Pre-fill for testing
const loading = ref(false)
const errorMessage = ref('')

// Kiểm tra nếu đã đăng nhập thì redirect
onMounted(() => {
    // Khởi tạo auth store
    authStore.initialize?.()

    if (authStore.isAuthenticated) {
        console.log('Already authenticated, redirecting to dashboard')
        router.push({ name: 'dashboard' })
    }
})

const handleLogin = async () => {
    // Reset error
    errorMessage.value = ''

    // Validate
    if (!email.value || !password.value) {
        errorMessage.value = 'Vui lòng nhập email và mật khẩu'
        return
    }

    if (!email.value.includes('@')) {
        errorMessage.value = 'Email không hợp lệ'
        return
    }

    try {
        loading.value = true
        console.log('Attempting login with:', {email: email.value})

        // Gọi auth store login
        await authStore.login({
            email: email.value,
            password: password.value
        })

        // Login successful - auth store sẽ tự redirect
        console.log('Login successful')

    } catch (error) {
        console.error('Login failed:', error)

        // Hiển thị lỗi chi tiết
        if (error.response) {
            // Server response với error
            const data = error.response.data
            errorMessage.value = data.message ||
                data.error ||
                'Đăng nhập thất bại. Vui lòng kiểm tra thông tin đăng nhập.'

            // Nếu là validation error
            if (data.errors) {
                const firstError = Object.values(data.errors)[0]
                if (firstError && firstError[0]) {
                    errorMessage.value = firstError[0]
                }
            }
        } else if (error.request) {
            // Không nhận được response
            errorMessage.value = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.'
        } else {
            // Lỗi khác
            errorMessage.value = error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.'
        }

        // Clear password
        password.value = ''

    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
/* Optional: Add some animations */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter, .fade-leave-to {
    opacity: 0;
}
</style>
