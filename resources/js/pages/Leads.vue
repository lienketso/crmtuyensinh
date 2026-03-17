<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản lý ứng viên</h1>
        <p class="text-gray-600 mt-1">Danh sách ứng viên tiềm năng</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="isAdmin"
          @click="openDistributeModal"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
        >
          <Shuffle class="w-4 h-4" />
          <span>Chia data</span>
        </button>
        <button
          @click="showImportModal = true"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition"
        >
          <Upload class="w-4 h-4" />
          <span>Import Excel dữ liệu ứng viên</span>
        </button>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          <Plus class="w-4 h-4" />
          <span>Tạo ứng viên mới</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Tìm kiếm..."
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          @input="loadLeads"
        />
        <select
          v-model="filters.status"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          @change="loadLeads"
        >
          <option value="">Tất cả trạng thái</option>
          <option value="new">Mới</option>
          <option value="contacted">Đã liên hệ</option>
          <option value="considering">Đang xem xét</option>
          <option value="enrolled">Đã đăng ký</option>
        </select>
        <select
          v-model="filters.source"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          @change="loadLeads"
        >
          <option value="">Tất cả nguồn</option>
          <option value="website">Website</option>
          <option value="facebook">Facebook</option>
          <option value="zalo">Zalo</option>
          <option value="manual">Nhập thủ công</option>
        </select>
        <select
          v-model="filters.year_of_admission"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          @change="loadLeads"
        >
          <option value="">Tất cả năm tuyển sinh</option>
          <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
      <div v-else-if="leads.length === 0" class="text-center py-12 text-gray-500">
        Chưa có leads nào
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Liên hệ</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nguồn</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giao cho</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="lead in leads"
              :key="lead.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="$router.push({ name: 'lead-detail', params: { id: lead.id } })"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ lead.name || 'Chưa có tên' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ lead.phone || '-' }}</div>
                <div class="text-sm text-gray-500">{{ lead.email || '' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                  {{ getSourceLabel(lead.source) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                <div v-if="isAdmin" class="min-w-44">
                  <select
                    class="w-full text-xs font-medium rounded-lg px-2 py-2 border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :value="lead.assigned_to ?? ''"
                    @change="assignLead(lead.id, $event.target.value)"
                  >
                    <option value="">-- Chưa gán --</option>
                    <option v-for="u in assignees" :key="u.id" :value="u.id">
                      {{ u.name }} ({{ u.role }})
                    </option>
                  </select>
                </div>
                <div v-else class="text-sm text-gray-700">
                  {{ lead.assigned_user?.name || lead.assignedUser?.name || 'Chưa gán' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select
                  :value="lead.status"
                  @change="updateStatus(lead.id, $event.target.value)"
                  @click.stop
                  class="text-xs font-medium rounded px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500"
                  :class="{
                    'bg-blue-100 text-blue-800': lead.status === 'new',
                    'bg-yellow-100 text-yellow-800': lead.status === 'contacted',
                    'bg-purple-100 text-purple-800': lead.status === 'considering',
                    'bg-green-100 text-green-800': lead.status === 'enrolled',
                    'bg-red-100 text-red-800': lead.status === 'lost'
                  }"
                >
                  <option value="new">Mới</option>
                  <option value="contacted">Đã liên hệ</option>
                  <option value="considering">Đang xem xét</option>
                  <option value="enrolled">Đã đăng ký</option>
                  <option value="lost">Mất</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(lead.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="inline-flex items-center gap-2">
                  <button
                    v-if="!lead.admission_profile"
                    type="button"
                    @click.stop="goCreateProfile(lead)"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="creatingProfileId === lead.id"
                    title="Tạo hồ sơ xét tuyển từ lead"
                  >
                    <FilePlus2 class="w-3 h-3" />
                    <span>{{ creatingProfileId === lead.id ? 'Đang tạo...' : 'Tạo hồ sơ' }}</span>
                  </button>
                  <button
                    type="button"
                    @click.stop="goCreateTask(lead)"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!isAdmin"
                    title="Tạo task theo lead"
                  >
                    <ListTodo class="w-3 h-3" />
                    <span>Tạo task</span>
                  </button>
                  <button
                    @click.stop="$router.push({ name: 'lead-detail', params: { id: lead.id } })"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Eye class="w-3 h-3" />
                    <span>Xem</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Lead Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showCreateModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">Tạo ứng viên mới</h2>
        <form @submit.prevent="createLead" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên</label>
            <input
              v-model="newLead.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
            <input
              v-model="newLead.phone"
              type="tel"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="newLead.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ngành học quan tâm</label>
            <input
              v-model="newLead.interest_course"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nguồn</label>
            <select
              v-model="newLead.source"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            >
              <option value="website">Website</option>
              <option value="facebook">Facebook</option>
              <option value="zalo">Zalo</option>
              <option value="manual">Nhập thủ công</option>
            </select>
          </div>
          <div v-if="isAdmin">
            <label class="block text-sm font-medium text-gray-700 mb-1">Giao cho</label>
            <select
              v-model="newLead.assigned_to"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            >
              <option :value="null">-- Chưa giao --</option>
              <option v-for="u in assignees" :key="u.id" :value="u.id">
                {{ u.name }} ({{ u.role }})
              </option>
            </select>
          </div>
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="showCreateModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ creating ? 'Đang tạo...' : 'Tạo' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Import Leads Modal -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeImportModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-2">Import ứng viên từ Excel/CSV</h2>
        <p class="text-sm text-gray-600 mb-4">
          File cần có dòng tiêu đề. Cột hỗ trợ: <b>name</b>, <b>phone</b>, <b>email</b>, <b>interest_course</b>, <b>source</b>, <b>status</b>, <b>assigned_to</b>.
        </p>

        <form @submit.prevent="importLeads" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chọn file</label>
            <input
              ref="importFileInput"
              type="file"
              accept=".xlsx,.xls,.csv"
              class="w-full text-sm"
              @change="onPickImportFile"
            />
          </div>

          <div v-if="importResult.message" class="rounded-lg border px-4 py-3 text-sm"
            :class="importResult.type === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'">
            <div class="font-semibold">{{ importResult.message }}</div>
            <div v-if="importResult.stats" class="mt-1">
              Tạo: <b>{{ importResult.stats.created }}</b>,
              Bỏ qua: <b>{{ importResult.stats.skipped }}</b>,
              Lỗi: <b>{{ importResult.stats.failed }}</b>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              @click="closeImportModal"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              <XIcon class="w-4 h-4" />
              <span>Đóng</span>
            </button>
            <button
              type="submit"
              :disabled="importing || !importFile"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
            >
              <Upload class="w-4 h-4" />
              <span>{{ importing ? 'Đang import...' : 'Import' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Distribute confirm modal -->
    <div
      v-if="showDistributeModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeDistributeModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <h2 class="text-xl font-semibold text-gray-900">Xác nhận chia data</h2>
          <p class="text-gray-600 mt-1">
            Hệ thống sẽ chia đều các ứng viên <b>chưa gán</b> (năm tuyển sinh: {{ (filters.year_of_admission || new Date().getFullYear()) }})
            cho các user role <b>advisor</b> và tự động tạo task công việc.
          </p>
        </div>

        <div class="p-6">
          <div v-if="distributeLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
          </div>

          <div v-else>
            <div v-if="distributeError" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">
              {{ distributeError }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
              <div class="p-4 rounded-lg bg-gray-50 border">
                <div class="text-sm text-gray-600">Tổng lead chưa gán</div>
                <div class="text-2xl font-bold text-gray-900">{{ distributePreview.total_leads ?? 0 }}</div>
              </div>
              <div class="p-4 rounded-lg bg-gray-50 border">
                <div class="text-sm text-gray-600">Số advisor</div>
                <div class="text-2xl font-bold text-gray-900">{{ (distributePreview.advisors || []).length }}</div>
              </div>
              <div class="p-4 rounded-lg bg-gray-50 border">
                <div class="text-sm text-gray-600">Task sẽ tạo (ước tính)</div>
                <div class="text-2xl font-bold text-gray-900">{{ distributePreview.total_leads ?? 0 }}</div>
              </div>
            </div>

            <div class="overflow-x-auto border rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Advisor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Số lead nhận</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="u in (distributePreview.advisors || [])" :key="u.id">
                    <td class="px-4 py-3">
                      <div class="text-sm font-medium text-gray-900">{{ u.name }}</div>
                      <div class="text-xs text-gray-500">{{ u.email }}</div>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-purple-50 text-purple-700">
                        {{ u.lead_count }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="(distributePreview.advisors || []).length === 0">
                    <td class="px-4 py-4 text-sm text-gray-500" colspan="2">Không có advisor để chia.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="p-6 border-t flex items-center justify-end gap-3">
          <button
            @click="closeDistributeModal"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition"
          >
            <XIcon class="w-4 h-4" />
            <span>Hủy</span>
          </button>
          <button
            :disabled="distributeExecuting || distributeLoading || (distributePreview.total_leads ?? 0) === 0 || (distributePreview.advisors || []).length === 0"
            @click="executeDistribute"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <Shuffle class="w-4 h-4" />
            <span v-if="distributeExecuting">Đang chia...</span>
            <span v-else>Xác nhận chia data</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/axios';
import { useAuthStore } from '@/stores/auth';
import { Plus, Upload, Eye, ListTodo, FilePlus2, Shuffle, X as XIcon } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();
const isAdmin = ref(false);
const assignees = ref([]);

const loading = ref(true);
const leads = ref([]);
const filters = ref({
  search: '',
  status: '',
  source: '',
  year_of_admission: ''
});
const showCreateModal = ref(false);
const creating = ref(false);
const creatingProfileId = ref(null);
const showImportModal = ref(false);
const importing = ref(false);
const importFile = ref(null);
const importFileInput = ref(null);
const importResult = ref({ type: '', message: '', stats: null });

const showDistributeModal = ref(false);
const distributeLoading = ref(false);
const distributeExecuting = ref(false);
const distributeError = ref('');
const distributePreview = ref({ total_leads: 0, advisors: [], year_of_admission: null });
const newLead = ref({
  name: '',
  phone: '',
  email: '',
  interest_course: '',
  source: 'manual',
  assigned_to: null,
});

const getSourceLabel = (source) => {
  const labels = {
    website: 'Website',
    facebook: 'Facebook',
    zalo: 'Zalo',
    manual: 'Nhập thủ công'
  };
  return labels[source] || source;
};

const years = ref([]);

const loadYears = async () => { 
  years.value = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).reverse();
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN');
};

const loadLeads = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.source) params.source = filters.value.source;
    if (filters.value.year_of_admission) params.year_of_admission = filters.value.year_of_admission;

    const response = await api.get('/leads-list', { params });

    leads.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Error loading leads:', error);
  } finally {
    loading.value = false;
  }
};

const loadAssignees = async () => {
  if (!isAdmin.value) return;
  try {
    const res = await api.get('/admin/users', { params: { per_page: 200 } });
    const list = res.data?.data || [];
    // Ưu tiên advisor để gán
    assignees.value = list
      .filter(u => u.role === 'advisor' || u.role === 'admin')
      .map(u => ({ id: u.id, name: u.name, role: u.role }));
  } catch (e) {
    console.error('Error loading users for assignment:', e);
  }
};

const updateStatus = async (id, status) => {
  try {
    await api.post(`/leads/${id}/status`, { status });
    await loadLeads();
  } catch (error) {
    console.error('Error updating status:', error);
    alert('Có lỗi xảy ra khi cập nhật trạng thái');
  }
};

const assignLead = async (id, assignedTo) => {
  try {
    const payload = {
      assigned_to: assignedTo === '' ? null : Number(assignedTo),
    };
    await api.post(`/leads/${id}/assign`, payload);
    await loadLeads();
  } catch (error) {
    console.error('Error assigning lead:', error);
    alert('Có lỗi xảy ra khi gán lead');
  }
};

const createLead = async () => {
  creating.value = true;
  try {
    await api.post('/lead-create', newLead.value);

    showCreateModal.value = false;
    newLead.value = {
      name: '',
      phone: '',
      email: '',
      interest_course: '',
      source: 'manual',
      assigned_to: null,
    };
    await loadLeads();
  } catch (error) {
    console.error('Error creating lead:', error);
    alert('Có lỗi xảy ra khi tạo lead');
  } finally {
    creating.value = false;
  }
};

const goCreateTask = (lead) => {
  router.push({ name: 'tasks', query: { lead_id: lead.id, open: '1' } });
};

const goCreateProfile = async (lead) => {
  creatingProfileId.value = lead.id;
  try {
    await api.post(`/leads/${lead.id}/admission-profile`, {
      identification_number: null,
      academic_records: [],
    });
    await loadLeads();
    alert('Đã tạo hồ sơ xét tuyển thành công.');
    router.push({ name: 'admission-profiles' });
  } catch (err) {
    const msg = err?.response?.data?.message || 'Tạo hồ sơ thất bại.';
    alert(msg);
  } finally {
    creatingProfileId.value = null;
  }
};

const closeImportModal = () => {
  showImportModal.value = false;
  importing.value = false;
  importFile.value = null;
  importResult.value = { type: '', message: '', stats: null };
  if (importFileInput.value) importFileInput.value.value = '';
};

const onPickImportFile = (e) => {
  const file = e.target?.files?.[0] || null;
  importFile.value = file;
  importResult.value = { type: '', message: '', stats: null };
};

const importLeads = async () => {
  if (!importFile.value) return;
  importing.value = true;
  importResult.value = { type: '', message: '', stats: null };
  try {
    const fd = new FormData();
    fd.append('file', importFile.value);
    const res = await api.post('/leads-import', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    importResult.value = {
      type: 'success',
      message: res.data?.message || 'Import thành công.',
      stats: {
        created: res.data?.created ?? 0,
        skipped: res.data?.skipped ?? 0,
        failed: res.data?.failed ?? 0,
      },
    };
    await loadLeads();
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      (error?.response?.data?.errors?.file?.[0]) ||
      'Import thất bại, vui lòng thử lại.';
    importResult.value = { type: 'error', message: msg, stats: null };
  } finally {
    importing.value = false;
  }
};

const closeDistributeModal = () => {
  showDistributeModal.value = false;
  distributeLoading.value = false;
  distributeExecuting.value = false;
  distributeError.value = '';
  distributePreview.value = { total_leads: 0, advisors: [], year_of_admission: null };
};

const openDistributeModal = async () => {
  showDistributeModal.value = true;
  distributeError.value = '';
  distributeLoading.value = true;
  try {
    const params = {};
    if (filters.value.year_of_admission) params.year_of_admission = filters.value.year_of_admission;
    const res = await api.get('/admin/leads/distribute/preview', { params });
    distributePreview.value = res.data || { total_leads: 0, advisors: [], year_of_admission: null };
  } catch (e) {
    distributeError.value =
      e?.response?.data?.message ||
      'Không thể lấy preview chia data. Vui lòng thử lại.';
  } finally {
    distributeLoading.value = false;
  }
};

const executeDistribute = async () => {
  distributeExecuting.value = true;
  distributeError.value = '';
  try {
    const payload = {};
    if (filters.value.year_of_admission) payload.year_of_admission = Number(filters.value.year_of_admission);
    const res = await api.post('/admin/leads/distribute/execute', payload);
    alert(res.data?.message || 'Đã chia data thành công.');
    closeDistributeModal();
    await loadLeads();
  } catch (e) {
    distributeError.value =
      e?.response?.data?.message ||
      'Chia data thất bại. Vui lòng thử lại.';
  } finally {
    distributeExecuting.value = false;
  }
};

onMounted(() => {
  authStore.initialize?.();
  isAdmin.value = authStore.user?.role === 'admin';
  loadAssignees();
  loadLeads();
  loadYears();
});
</script>
