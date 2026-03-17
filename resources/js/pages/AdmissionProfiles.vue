<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/utils/axios';
import { RefreshCcw, FileText, CheckCircle2 } from 'lucide-vue-next';

const profiles = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchProfiles = async () => {
  try {
    loading.value = true;
    const response = await api.get('/admission-profiles');
    profiles.value = response.data?.data ?? response.data ?? [];
  } catch (error) {
    console.error('Lỗi khi tải hồ sơ:', error);
    profiles.value = [];
  } finally {
    loading.value = false;
  }
};

const verifyProfile = async (id) => {
  const confirmed = window.confirm(
    'Bạn có chắc chắn muốn duyệt hồ sơ này và chuyển trạng thái sang "Hồ sơ hợp lệ"?'
  );
  if (!confirmed) return;

  try {
    await api.post(`/admission-profiles/${id}/verify`);
    await fetchProfiles();
  } catch (error) {
    console.error('Lỗi khi duyệt hồ sơ:', error);
    window.alert('Không thể duyệt hồ sơ. Vui lòng thử lại.');
  }
};

// Lọc hồ sơ theo tìm kiếm
const filteredProfiles = computed(() => {
  // Kiểm tra nếu profiles.value không tồn tại hoặc không phải mảng thì trả về mảng rỗng
  if (!profiles.value || !Array.isArray(profiles.value)) {
    return [];
  }

  return profiles.value.filter(p => {
    const name = (p.lead?.name || p.full_name || '').toLowerCase();
    const phone = p.lead?.phone || p.phone || '';
    const query = searchQuery.value.toLowerCase();
    return name.includes(query) || String(phone).includes(query);
  });
});

// Helper định dạng màu + nhãn trạng thái
const statusClass = (status) => {
  const base = "px-2 py-1 rounded-full text-xs font-medium ";
  const map = {
    not_registered: "bg-gray-100 text-gray-700",
    registered: "bg-yellow-100 text-yellow-700",
    submitted: "bg-blue-100 text-blue-700",
    need_more_docs: "bg-amber-100 text-amber-700",
    valid: "bg-emerald-100 text-emerald-700",
    in_review: "bg-indigo-100 text-indigo-700",
    admitted: "bg-purple-100 text-purple-700",
    confirmed: "bg-teal-100 text-teal-700",
    enrolled: "bg-green-100 text-green-700",
    // Hỗ trợ các trạng thái cũ
    pending: "bg-yellow-100 text-yellow-700",
    verified: "bg-emerald-100 text-emerald-700",
    rejected: "bg-red-100 text-red-700",
  };
  return base + (map[status] || "bg-gray-100 text-gray-700");
};

const statusLabel = (status) => {
  const map = {
    not_registered: 'Chưa đăng ký',
    registered: 'Đã đăng ký xét tuyển',
    submitted: 'Đã nộp hồ sơ',
    need_more_docs: 'Hồ sơ cần bổ sung',
    valid: 'Hồ sơ hợp lệ',
    in_review: 'Đang xét tuyển',
    admitted: 'Trúng tuyển',
    confirmed: 'Xác nhận nhập học',
    enrolled: 'Đã nhập học',
    pending: 'Chờ xử lý',
    verified: 'Hồ sơ hợp lệ',
    rejected: 'Từ chối',
  };
  return map[status] || 'Đang xử lý';
};
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  // Kiểm tra xem date có hợp lệ không
  return isNaN(date.getTime()) 
    ? dateString 
    : date.toLocaleDateString('vi-VN'); // Định dạng theo kiểu Việt Nam: DD/MM/YYYY
};


onMounted(fetchProfiles);
</script>

<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Danh sách Hồ sơ Xét tuyển</h1>
        <p class="text-sm text-gray-500 text-left">Quản lý và duyệt hồ sơ thí sinh từ hệ thống AI</p>
      </div>
      <button
        @click="fetchProfiles"
        class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm font-semibold"
      >
        <RefreshCcw class="w-4 h-4" />
        <span>Làm mới</span>
      </button>
    </div>

    <div class="mb-4">
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Tìm tên thí sinh hoặc số điện thoại..." 
        class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
      />
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ và Tên</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngành đăng ký</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm trung bình</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày nộp</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình thức xét tuyển</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="profile in filteredProfiles" :key="profile.id" class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-semibold text-gray-900">{{ profile.lead.name }}</div>
              <div class="text-sm text-gray-500">Điện thoại: {{ profile.lead.phone }}</div>
              <div class="text-sm text-gray-500">CCCD: {{ profile.identification_number }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ profile.lead.interest_course }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ profile.gpa }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(profile.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ profile.admission_method }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="statusClass(profile.document_status)">
                {{ statusLabel(profile.document_status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link
                :to="{ name: 'admission-profile-detail', params: { id: profile.id } }"
                class="inline-flex items-center gap-1.5 border border-blue-600 rounded-lg px-3 py-1.5 text-xs sm:text-sm text-blue-600 hover:text-blue-900 mr-2"
              >
                <FileText class="w-3 h-3" />
                <span>Cập nhật</span>
              </router-link>
              <button
                @click="verifyProfile(profile.id)"
                class="inline-flex items-center gap-1.5 border border-green-600 rounded-lg px-3 py-1.5 text-xs sm:text-sm text-green-600 hover:text-green-900"
              >
                <CheckCircle2 class="w-3 h-3" />
                <span>Duyệt</span>
              </button>
            </td>
          </tr>
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Đang tải dữ liệu...</td>
          </tr>
          <tr v-if="!loading && filteredProfiles.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Không tìm thấy hồ sơ nào.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>