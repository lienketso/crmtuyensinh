<script setup>
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { UserPlus2, Edit3, Trash2, RefreshCcw, Search } from 'lucide-vue-next'

const authStore = useAuthStore()
const { get, post, put, del, loading, error } = useApi()

const pageLoading = ref(false)
const flash = ref({ type: '', message: '' })

const users = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

const query = ref('')

const modalOpen = ref(false)
const modalMode = ref('create') // create | edit
const activeUserId = ref(null)
const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'advisor',
})
const formErrors = ref({})

const isAdmin = computed(() => authStore.user?.role === 'admin')

const roleBadge = (role) => {
  if (role === 'admin') return 'bg-purple-100 text-purple-800 ring-purple-200'
  return 'bg-blue-100 text-blue-800 ring-blue-200'
}

const roleLabel = (role) => (role === 'admin' ? 'Admin' : 'Advisor')

const setFlash = (type, message) => {
  flash.value = { type, message }
  window.setTimeout(() => {
    if (flash.value.message === message) flash.value = { type: '', message: '' }
  }, 3500)
}

const resetForm = () => {
  activeUserId.value = null
  form.value = {
    name: '',
    email: '',
    password: '',
    role: 'advisor',
  }
  formErrors.value = {}
}

const openCreate = () => {
  modalMode.value = 'create'
  resetForm()
  modalOpen.value = true
}

const openEdit = (u) => {
  modalMode.value = 'edit'
  activeUserId.value = u.id
  form.value = {
    name: u.name ?? '',
    email: u.email ?? '',
    password: '',
    role: u.role ?? 'advisor',
  }
  formErrors.value = {}
  modalOpen.value = true
}

const closeModal = () => {
  modalOpen.value = false
}

const fetchUsers = async (page = 1) => {
  pageLoading.value = true
  try {
    const data = await get('/admin/users', {
      params: {
        page,
        q: query.value || undefined,
      },
    })
    users.value = data.data ?? []
    pagination.value = {
      current_page: data.current_page ?? 1,
      last_page: data.last_page ?? 1,
      per_page: data.per_page ?? 20,
      total: data.total ?? 0,
    }
  } catch (e) {
    // handled by interceptor + error ref
  } finally {
    pageLoading.value = false
  }
}

const submit = async () => {
  formErrors.value = {}
  try {
    if (modalMode.value === 'create') {
      await post('/admin/users', form.value)
      setFlash('success', 'Tạo người dùng thành công.')
    } else {
      await put(`/admin/users/${activeUserId.value}`, form.value)
      setFlash('success', 'Cập nhật người dùng thành công.')
    }
    closeModal()
    await fetchUsers(pagination.value.current_page)
  } catch (e) {
    if (e?.response?.status === 422) {
      formErrors.value = e.response.data?.errors ?? {}
      setFlash('error', 'Vui lòng kiểm tra lại dữ liệu nhập.')
      return
    }
    setFlash('error', e?.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại.')
  }
}

const confirmDelete = async (u) => {
  if (!window.confirm(`Xóa người dùng "${u.name}"?`)) return
  try {
    await del(`/admin/users/${u.id}`)
    setFlash('success', 'Đã xóa người dùng.')
    await fetchUsers(pagination.value.current_page)
  } catch (e) {
    setFlash('error', e?.response?.data?.message || 'Không thể xóa người dùng.')
  }
}

const emptyStateText = computed(() => {
  if (query.value) return 'Không tìm thấy người dùng phù hợp.'
  return 'Chưa có người dùng nào.'
})

onMounted(() => {
  fetchUsers(1)
})
</script>

<template>
  <div>
    <div class="flex items-start justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản lý người dùng</h1>
        <p class="text-gray-600 mt-1">
          Chỉ <span class="font-semibold">Admin</span> mới có quyền tạo / sửa / xoá người dùng.
        </p>
      </div>

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="!isAdmin"
        @click="openCreate"
      >
        <UserPlus2 class="w-4 h-4" />
        <span>Tạo người dùng</span>
      </button>
    </div>

    <div
      v-if="flash.message"
      class="mb-4 rounded-lg border px-4 py-3 text-sm"
      :class="flash.type === 'success'
        ? 'border-green-200 bg-green-50 text-green-800'
        : 'border-red-200 bg-red-50 text-red-800'"
    >
      {{ flash.message }}
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
      <div class="p-4 md:p-5 border-b border-gray-200 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
        <div class="w-full md:max-w-sm">
          <label class="sr-only" for="q">Tìm kiếm</label>
          <div class="relative">
            <input
              id="q"
              v-model="query"
              type="text"
              placeholder="Tìm theo tên hoặc email…"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 pl-10 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @keyup.enter="fetchUsers(1)"
            />
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <Search class="h-4 w-4" />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            :disabled="pageLoading"
            @click="fetchUsers(1)"
          >
            <RefreshCcw class="w-4 h-4" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Người dùng</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vai trò</th>
              <th class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Thao tác</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="pageLoading">
              <td colspan="3" class="px-5 py-10 text-center text-gray-500">
                Đang tải dữ liệu…
              </td>
            </tr>

            <tr v-else-if="users.length === 0">
              <td colspan="3" class="px-5 py-10 text-center text-gray-500">
                {{ emptyStateText }}
              </td>
            </tr>

            <tr v-else v-for="u in users" :key="u.id" class="hover:bg-gray-50">
              <td class="px-5 py-4">
                <div class="font-semibold text-gray-900">{{ u.name }}</div>
                <div class="text-sm text-gray-500">{{ u.email }}</div>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                  :class="roleBadge(u.role)"
                >
                  {{ roleLabel(u.role) }}
                </span>
              </td>
              <td class="px-5 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!isAdmin"
                    @click="openEdit(u)"
                  >
                    <Edit3 class="w-3 h-3" />
                    <span>Sửa</span>
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!isAdmin"
                    @click="confirmDelete(u)"
                  >
                    <Trash2 class="w-3 h-3" />
                    <span>Xóa</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="p-4 md:p-5 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
        <div>
          Tổng: <span class="font-semibold text-gray-900">{{ pagination.total }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="pagination.current_page <= 1 || pageLoading"
            @click="fetchUsers(pagination.current_page - 1)"
          >
            Trước
          </button>
          <span class="tabular-nums">
            Trang <span class="font-semibold text-gray-900">{{ pagination.current_page }}</span>
            / {{ pagination.last_page }}
          </span>
          <button
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="pagination.current_page >= pagination.last_page || pageLoading"
            @click="fetchUsers(pagination.current_page + 1)"
          >
            Sau
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ error.message || 'Có lỗi xảy ra.' }}
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/50" @click="closeModal"></div>

      <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-gray-200">
        <div class="flex items-start justify-between gap-4 p-5 border-b border-gray-200">
          <div>
            <h2 class="text-lg font-bold text-gray-900">
              {{ modalMode === 'create' ? 'Tạo người dùng' : 'Cập nhật người dùng' }}
            </h2>
            <p class="text-sm text-gray-600 mt-1">
              {{ modalMode === 'create' ? 'Nhập thông tin để tạo user mới.' : 'Chỉnh sửa thông tin user.' }}
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            @click="closeModal"
          >
            <span class="sr-only">Đóng</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>

        <form class="p-5 space-y-4" @submit.prevent="submit">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Họ tên</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Nguyễn Văn A"
            />
            <p v-if="formErrors.name?.[0]" class="mt-1 text-sm text-red-600">{{ formErrors.name[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="user@example.com"
            />
            <p v-if="formErrors.email?.[0]" class="mt-1 text-sm text-red-600">{{ formErrors.email[0] }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">
                Mật khẩu
                <span class="text-xs font-normal text-gray-500">
                  ({{ modalMode === 'edit' ? 'để trống nếu không đổi' : 'bắt buộc' }})
                </span>
              </label>
              <input
                v-model="form.password"
                type="password"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="••••••"
              />
              <p v-if="formErrors.password?.[0]" class="mt-1 text-sm text-red-600">{{ formErrors.password[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Vai trò</label>
              <select
                v-model="form.role"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="advisor">Advisor</option>
                <option value="admin">Admin</option>
              </select>
              <p v-if="formErrors.role?.[0]" class="mt-1 text-sm text-red-600">{{ formErrors.role[0] }}</p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
              @click="closeModal"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading"
            >
              {{ loading ? 'Đang lưu…' : (modalMode === 'create' ? 'Tạo' : 'Lưu thay đổi') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

