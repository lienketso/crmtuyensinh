import { ref } from 'vue'
import api from '@/utils/axios'

export function useApi() {
    const loading = ref(false)
    const error = ref(null)

    // Generic GET request
    const get = async (url, config = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await api.get(url, config)
            return response.data
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    // Generic POST request
    const post = async (url, data, config = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await api.post(url, data, config)
            return response.data
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    // Generic PUT request
    const put = async (url, data, config = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await api.put(url, data, config)
            return response.data
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    // Generic DELETE request
    const del = async (url, config = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await api.delete(url, config)
            return response.data
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        get,
        post,
        put,
        del
    }
}
