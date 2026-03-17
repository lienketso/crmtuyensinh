<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/axios';
import { Plus, Filter, Edit3, CheckCircle2, X as XIcon } from 'lucide-vue-next';

const pageLoading = ref(false)
const flash = ref({ type: '', message: '' })

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { get, post, put, del, loading, error } = useApi()
const setFlash = (type, message) => {
  flash.value = { type, message }
  window.setTimeout(() => {
    if (flash.value.message === message) flash.value = { type: '', message: '' }
  }, 3500)
}
const tasks = ref([])
const isAdmin = computed(() => authStore.user?.role === 'admin')
const statusBadge = (status) => {
  if (status === 'pending') return 'bg-purple-100 text-purple-800 ring-purple-200'
  if (status === 'done') return 'bg-green-100 text-green-800 ring-green-200'
  if (status === 'overdue') return 'bg-red-100 text-red-800 ring-red-200'
  if (status === 'cancelled') return 'bg-gray-100 text-gray-800 ring-gray-200'
}
const statusLabel = (status) => {
  if (status === 'pending') return 'Chờ xử lý'
  if (status === 'done') return 'Đã hoàn thành'
  if (status === 'overdue') return 'Quá hạn'
  if (status === 'cancelled') return 'Đã hủy'
}
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})
const modalOpen = ref(false)
const modalMode = ref('create') // create | edit
const form = ref({
  lead_id: null,
  title: '',
  description: '',
  due_at: '',
  status: 'pending',
})

const leads = ref([])
const filters = ref({ lead_id: '', status: '' })
const stats = ref({ done: 0, pending: 0, soon_due: 0 })

const fetchLeads = async () => {
  const data = await get('/leads-list', { params: { page: 1, per_page: 500 } })
  leads.value = data.data ?? []
}

const fetchStats = async () => {
  try {
    const data = await get('/tasks-stats')
    stats.value = {
      done: data.done ?? 0,
      pending: data.pending ?? 0,
      soon_due: data.soon_due ?? 0,
    }
  } catch {
    stats.value = { done: 0, pending: 0, soon_due: 0 }
  }
}

const fetchTasks = async (page = 1) => {
  pageLoading.value = true
  try {
    const params = { page }
    if (filters.value.lead_id) params.lead_id = filters.value.lead_id
    if (filters.value.status) params.status = filters.value.status
    const data = await get('/tasks-list', { params })
    tasks.value = data.data ?? []
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

const applyFilters = () => {
  fetchTasks(1)
}   
const completeTask = async (task) => {
  pageLoading.value = true
  if (!window.confirm(`Bạn có chắc chắn muốn hoàn thành công việc "${task.title}"?`)) return
  try {
    await post(`/tasks/${task.id}/done`, { id: task.id })
    await fetchTasks(pagination.value.current_page)
    await fetchStats()
    setFlash('success', 'Công việc đã hoàn thành.')
  } catch (e) {
    // handled by interceptor + error ref
    setFlash('error', e?.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại.')
  } finally {
    pageLoading.value = false
  }
}
const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' });
};
const openCreateTaskModal = () => {
  modalMode.value = 'create'
  form.value = {
    lead_id: null,
    title: '',
    description: '',
    due_at: '',
    status: 'pending',
  }
  modalOpen.value = true
}
const closeCreateTaskModal = () => {
  modalOpen.value = false
  editingTaskId.value = null
  resetForm()
}

const resetForm = () => {
  form.value = {
    lead_id: null,
    title: '',
    description: '',
    due_at: '',
    status: 'pending',
  }
}
const newTask = ref({
  lead_id: null,
  title: '',
  description: '',
  due_at: '',
  status: 'pending',
})
const editingTaskId = ref(null)

const openEditTaskModal = (task) => {
  modalMode.value = 'edit'
  editingTaskId.value = task.id
  const dueAt = task.due_at ? task.due_at.slice(0, 10) : ''
  newTask.value = {
    lead_id: task.lead_id ?? null,
    title: task.title ?? '',
    description: task.description ?? '',
    due_at: dueAt,
    status: task.status ?? 'pending',
  }
  modalOpen.value = true
}

const openCreateTaskForLeadId = (leadId) => {
  if (!isAdmin.value) return
  openCreateTaskModal()
  newTask.value = {
    lead_id: leadId,
    title: '',
    description: '',
    due_at: '',
    status: 'pending',
  }
}
const submitTask = async () => {
  try {
    if (modalMode.value === 'edit' && editingTaskId.value) {
      await put(`/tasks/${editingTaskId.value}`, newTask.value)
      await fetchTasks(pagination.value.current_page)
      await fetchStats()
      setFlash('success', 'Đã cập nhật công việc.')
      modalOpen.value = false
      editingTaskId.value = null
    } else {
      await post('/tasks-create', newTask.value)
      await fetchTasks(1)
      await fetchStats()
      setFlash('success', 'Công việc đã tạo.')
      modalOpen.value = false
      if (route.query?.open === '1') {
        const nextQuery = { ...route.query }
        delete nextQuery.open
        router.replace({ query: nextQuery })
      }
    }
  } catch (e) {
    setFlash('error', e?.response?.data?.message || e?.response?.data?.errors ? 'Dữ liệu không hợp lệ.' : 'Có lỗi xảy ra, vui lòng thử lại.')
  }
}

onMounted(() => {
  authStore.initialize?.()
  fetchLeads()
  fetchStats()
  fetchTasks(1)
})

watch(
  () => [route.query?.lead_id, route.query?.open, isAdmin.value],
  async ([leadId, open]) => {
    if (!leadId) return
    if (open !== '1') return
    const parsed = Number(leadId)
    if (!Number.isFinite(parsed)) return
    openCreateTaskForLeadId(parsed)
  },
  { immediate: true }
)


</script>


<template>
    <div>
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Quản lý công việc</h1>
          <p class="text-gray-600 mt-1">
            Quản lý công việc của chuyên viên.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!isAdmin"
          @click="openCreateTaskModal"
        >
          <Plus class="w-4 h-4" />
          <span>Tạo công việc</span>
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

      <!-- Cards thống kê -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
          <p class="text-sm font-medium text-gray-600">Đã hoàn thành</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.done }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
          <p class="text-sm font-medium text-gray-600">Sắp hết hạn</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.soon_due }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
          <p class="text-sm font-medium text-gray-600">Chưa xử lý</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.pending }}</p>
        </div>
      </div>

      <!-- Bộ lọc -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <p class="text-sm font-medium text-gray-700 mb-3">Lọc</p>
        <div class="flex flex-wrap items-end gap-3">
          <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Lead</label>
            <select
              v-model="filters.lead_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
              @change="applyFilters"
            >
              <option value="">Tất cả lead</option>
              <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.name || lead.phone || 'Lead #' + lead.id }}</option>
            </select>
          </div>
          <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
            <select
              v-model="filters.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
              @change="applyFilters"
            >
              <option value="">Tất cả</option>
              <option value="pending">Chờ xử lý</option>
              <option value="done">Đã hoàn thành</option>
              <option value="overdue">Quá hạn</option>
            </select>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium"
            @click="filters.lead_id = ''; filters.status = ''; applyFilters()"
          >
            <Filter class="w-4 h-4" />
            <span>Xóa bộ lọc</span>
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Lead</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tiêu đề</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Trạng thái</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày hạn</th>
              <th class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Thao tác</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="pageLoading">
              <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                Đang tải dữ liệu…
              </td>
            </tr>

            <tr v-else-if="tasks.length === 0">
              <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                Chưa có công việc nào
              </td>
            </tr>

            <tr v-else v-for="task in tasks" :key="task.id" class="hover:bg-gray-50">
              <td class="px-5 py-4">
                <div class="font-semibold text-gray-900">{{ task.lead?.name || '—' }}</div>
              </td>
              <td class="px-5 py-4">
                <div class="font-semibold text-gray-900">{{ task.title }}</div>
                <div class="text-sm text-gray-500">{{ task.description }}</div>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                  :class="statusBadge(task.status)"
                >
                    {{ statusLabel(task.status) }}
                </span>
              </td>
              <td class="px-5 py-4">
                <div class="font-semibold text-gray-900">{{ formatDate(task.due_at) }}</div>
              </td>
             
              <td class="px-5 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="openEditTaskModal(task)"
                  >
                    <Edit3 class="w-3 h-3" />
                    <span>Sửa</span>
                  </button>
                  <template v-if="task.status === 'pending'">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      @click="completeTask(task)"
                    >
                      <CheckCircle2 class="w-3 h-3" />
                      <span>Hoàn thành</span>
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
    <div
      v-if="modalOpen"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeCreateTaskModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">{{ modalMode === 'edit' ? 'Cập nhật công việc' : 'Tạo công việc' }}</h2>
        <form @submit.prevent="submitTask" class="space-y-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Lead</label>
            <select v-model="newTask.lead_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
              <option :value="null">Chọn lead</option>
              <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.name || lead.phone || 'Lead #' + lead.id }}</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề</label>
            <input v-model="newTask.title" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea v-model="newTask.description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày hạn</label>
            <input v-model="newTask.due_at" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
          </div>
          <div v-if="modalMode === 'edit'" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
            <select v-model="newTask.status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
              <option value="pending">Chờ xử lý</option>
              <option value="done">Đã hoàn thành</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </div>
          <div class="flex items-center justify-end gap-2">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              <CheckCircle2 class="w-4 h-4" />
              <span>{{ modalMode === 'edit' ? 'Cập nhật' : 'Tạo' }}</span>
            </button>
            <button
              type="button"
              class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
              @click="closeCreateTaskModal"
            >
              <XIcon class="w-4 h-4" />
              <span>Hủy</span>
            </button>
          </div>
        </form>
      </div>
    </div>
    
</template>