import axios from 'axios'
import router from '@/router'

const normalizePath = (p) => {
    if (!p) return ''
    let s = String(p)
    // Remove trailing slash
    s = s.replace(/\/$/, '')
    // Ensure leading slash (unless empty)
    if (s && !s.startsWith('/')) s = '/' + s
    return s
}

const detectBaseURL = () => {
    const env = import.meta.env.VITE_API_BASE_URL
    if (env) return env.replace(/\/$/, '')

    // Prefer Laravel-provided base path if available
    if (window.APP_BASE_PATH) {
        try {
            const url = new URL(window.APP_BASE_PATH)
            const basePath = normalizePath(url.pathname)
            return window.location.origin + basePath + '/api'
        } catch (e) {
            // If not a full URL, treat as path
            const basePath = normalizePath(window.APP_BASE_PATH)
            return window.location.origin + basePath + '/api'
        }
    }

    // Fallback: auto-detect common XAMPP subdir (/.../public)
    const pathname = (window.location.pathname || '').replace(/\/$/, '')
    if (pathname.includes('/public')) {
        const idx = pathname.indexOf('/public')
        const basePath = pathname.substring(0, idx + '/public'.length)
        return window.location.origin + basePath + '/api'
    }

    return window.location.origin + '/api'
}

// Lấy base URL (chuẩn hoá) cho toàn bộ API
const baseURL = detectBaseURL()

// Tạo axios instance
const api = axios.create({
    baseURL: baseURL,
    timeout: 30000, // 30 seconds timeout
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Helper function để lấy token
const getToken = () => {
    // Ưu tiên localStorage, sau đó sessionStorage
    return localStorage.getItem('access_token') ||
        sessionStorage.getItem('access_token')
}

// Request interceptor - tự động thêm token vào headers
api.interceptors.request.use(
    (config) => {
        const token = getToken()

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        } else {
            console.warn(`[API Warning] No token found for ${config.url}`)
        }

        return config
    },
    (error) => {
        console.error('[API Request Error]', error)
        return Promise.reject(error)
    }
)

// Response interceptor - xử lý lỗi tự động
api.interceptors.response.use(
    (response) => {
        return response
    },
    (error) => {
        const { response, config } = error

        console.error('[API Error]', {
            url: config?.url,
            method: config?.method,
            status: response?.status,
            message: error.message
        })

        // Xử lý lỗi 401 Unauthorized
        if (response?.status === 401) {
            console.log('[API] 401 Unauthorized - Clearing tokens')

            // Xóa tokens
            localStorage.removeItem('access_token')
            localStorage.removeItem('user')
            sessionStorage.removeItem('access_token')
            sessionStorage.removeItem('user')

            // Redirect bằng Vue Router để giữ đúng base path (tránh redirect sai khi app chạy trong subdirectory)
            if (!window.location.pathname.includes('login')) {
                router.push({ name: 'login-admin' }).catch(() => {
                    window.location.href = (window.APP_BASE_PATH || '').replace(/\/$/, '') + '/login-admin'
                })
            }
        }

        // Xử lý lỗi 403 Forbidden
        if (response?.status === 403) {
            console.error('[API] 403 Forbidden - Không có quyền truy cập')
            // Có thể hiển thị thông báo cho người dùng
        }

        // Xử lý lỗi 404 Not Found
        if (response?.status === 404) {
            console.error('[API] 404 Not Found - API endpoint không tồn tại')
        }

        // Xử lý lỗi 422 Validation Error
        if (response?.status === 422) {
            console.error('[API] 422 Validation Error:', response.data.errors)
        }

        // Xử lý lỗi 500 Server Error
        if (response?.status >= 500) {
            console.error('[API] Server Error:', response.status)
        }

        // Network error
        if (!response) {
            console.error('[API] Network Error - Không thể kết nối đến server')
        }

        return Promise.reject(error)
    }
)

// Export instance
export default api
