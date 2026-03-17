<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản lý trường học</h1>
        <p class="text-gray-600 mt-1">Chỉ super admin mới truy cập được module này.</p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        <Plus class="w-4 h-4" />
        <span>Thêm trường</span>
      </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="py-10 text-center">
        <div class="inline-block h-8 w-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
      </div>
      <div v-else-if="schools.length === 0" class="py-10 text-center text-gray-500">
        Chưa có trường nào.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email liên hệ</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="s in schools" :key="s.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                {{ s.name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ s.domain || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ s.contact_email || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-right space-x-2">
                <button
                  @click="openEditModal(s)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50"
                >
                  <Edit3 class="w-3 h-3" />
                  <span>Sửa</span>
                </button>
                <button
                  @click="openCreateAdminModal(s)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-indigo-500 text-indigo-600 rounded-lg text-xs font-medium hover:bg-indigo-50"
                >
                  <UserPlus2 class="w-3 h-3" />
                  <span>Tạo admin</span>
                </button>
                <button
                  @click="deleteSchool(s)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-500 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50"
                >
                  <Trash2 class="w-3 h-3" />
                  <span>Xoá</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal tạo / sửa trường -->
    <div
      v-if="showSchoolModal"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 p-4"
      @click.self="closeSchoolModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
          {{ editingSchool ? 'Sửa trường học' : 'Thêm trường học' }}
        </h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên trường *</label>
            <input
              v-model="schoolForm.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
            <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
            <input
              v-model="schoolForm.domain"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="vd: example.edu.vn"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email liên hệ</label>
            <input
              v-model="schoolForm.contact_email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="contact@example.edu.vn"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea
              v-model="schoolForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            ></textarea>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="closeSchoolModal"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <XIcon class="w-4 h-4" />
            <span>Hủy</span>
          </button>
          <button
            @click="saveSchool"
            :disabled="savingSchool"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <Save class="w-4 h-4" />
            <span>{{ savingSchool ? 'Đang lưu...' : 'Lưu' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal tạo admin cho trường -->
    <div
      v-if="showAdminModal"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 p-4"
      @click.self="closeAdminModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
          Tạo admin cho trường: {{ selectedSchool?.name }}
        </h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên admin *</label>
            <input
              v-model="adminForm.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
            <p v-if="adminErrors.name" class="mt-1 text-xs text-red-600">{{ adminErrors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input
              v-model="adminForm.email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="admin@school.edu.vn"
            />
            <p v-if="adminErrors.email" class="mt-1 text-xs text-red-600">{{ adminErrors.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu (tuỳ chọn)</label>
            <input
              v-model="adminForm.password"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Để trống để hệ thống tự sinh"
            />
          </div>
          <div v-if="generatedPassword" class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700">
            <p class="font-medium mb-1">Mật khẩu được tạo:</p>
            <p class="font-mono break-all">{{ generatedPassword }}</p>
            <p class="mt-1 text-[11px] text-gray-500">
              Vui lòng sao chép và gửi cho admin của trường. Hệ thống không lưu mật khẩu này ở dạng plain text.
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="closeAdminModal"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <XIcon class="w-4 h-4" />
            <span>Hủy</span>
          </button>
          <button
            @click="createAdmin"
            :disabled="creatingAdmin"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
          >
            <UserPlus2 class="w-4 h-4" />
            <span>{{ creatingAdmin ? 'Đang tạo...' : 'Tạo admin' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/axios';
import { Plus, Edit3, Trash2, UserPlus2, Save, X as XIcon } from 'lucide-vue-next';

const loading = ref(true);
const schools = ref([]);

const showSchoolModal = ref(false);
const savingSchool = ref(false);
const editingSchool = ref(null);
const schoolForm = ref({
  name: '',
  domain: '',
  description: '',
  contact_email: '',
});
const errors = ref({});

const showAdminModal = ref(false);
const creatingAdmin = ref(false);
const selectedSchool = ref(null);
const adminForm = ref({
  name: '',
  email: '',
  password: '',
});
const adminErrors = ref({});
const generatedPassword = ref('');

const resetSchoolForm = () => {
  schoolForm.value = {
    name: '',
    domain: '',
    description: '',
    contact_email: '',
  };
  errors.value = {};
  editingSchool.value = null;
};

const openCreateModal = () => {
  resetSchoolForm();
  showSchoolModal.value = true;
};

const openEditModal = (school) => {
  editingSchool.value = school;
  schoolForm.value = {
    name: school.name,
    domain: school.domain || '',
    description: school.description || '',
    contact_email: school.contact_email || '',
  };
  errors.value = {};
  showSchoolModal.value = true;
};

const closeSchoolModal = () => {
  showSchoolModal.value = false;
};

const fetchSchools = async () => {
  loading.value = true;
  try {
    const res = await api.get('/super-admin/schools', { params: { per_page: 100 } });
    const data = res.data;
    schools.value = data?.data || data || [];
  } catch (e) {
    console.error('Error loading schools:', e);
    alert('Không thể tải danh sách trường học.');
  } finally {
    loading.value = false;
  }
};

const saveSchool = async () => {
  savingSchool.value = true;
  errors.value = {};
  try {
    if (editingSchool.value) {
      await api.put(`/super-admin/schools/${editingSchool.value.id}`, schoolForm.value);
    } else {
      await api.post('/super-admin/schools', schoolForm.value);
    }
    showSchoolModal.value = false;
    await fetchSchools();
  } catch (e) {
    console.error('Error saving school:', e);
    const errs = e?.response?.data?.errors || {};
    errors.value = {
      name: errs.name?.[0],
    };
    alert(e?.response?.data?.message || 'Lưu trường học thất bại.');
  } finally {
    savingSchool.value = false;
  }
};

const deleteSchool = async (school) => {
  if (!confirm(`Bạn có chắc chắn muốn xoá trường "${school.name}"?`)) return;
  try {
    await api.delete(`/super-admin/schools/${school.id}`);
    await fetchSchools();
  } catch (e) {
    console.error('Error deleting school:', e);
    alert(e?.response?.data?.message || 'Xoá trường học thất bại.');
  }
};

const openCreateAdminModal = (school) => {
  selectedSchool.value = school;
  adminForm.value = {
    name: '',
    email: '',
    password: '',
  };
  adminErrors.value = {};
  generatedPassword.value = '';
  showAdminModal.value = true;
};

const closeAdminModal = () => {
  showAdminModal.value = false;
};

const createAdmin = async () => {
  if (!selectedSchool.value) return;
  creatingAdmin.value = true;
  adminErrors.value = {};
  generatedPassword.value = '';
  try {
    const res = await api.post(`/super-admin/schools/${selectedSchool.value.id}/admins`, adminForm.value);
    const data = res.data || {};
    generatedPassword.value = data.plain_password || '';
    alert(data.message || 'Đã tạo admin cho trường thành công.');
  } catch (e) {
    console.error('Error creating school admin:', e);
    const errs = e?.response?.data?.errors || {};
    adminErrors.value = {
      name: errs.name?.[0],
      email: errs.email?.[0],
      password: errs.password?.[0],
    };
    alert(e?.response?.data?.message || 'Tạo admin cho trường thất bại.');
  } finally {
    creatingAdmin.value = false;
  }
};

onMounted(() => {
  fetchSchools();
});
</script>

