<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản lý Khoá học</h1>
        <p class="text-gray-600 mt-1">Danh sách các khoá học</p>
      </div>
      <button
        v-if="isAdmin"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        + Tạo khoá học
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="courses.length === 0" class="text-center py-12">
      <p class="text-gray-500 mb-4">Chưa có khoá học nào</p>
      <button
        v-if="isAdmin"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Tạo khoá học đầu tiên
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="course in courses"
        :key="course.id"
        class="bg-white rounded-lg shadow hover:shadow-lg transition p-6"
      >
        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ course.name }}</h3>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ course.description }}</p>

        <div class="space-y-2 mb-4">
          <div class="flex items-center text-sm text-gray-600">
            <span class="mr-2">⏱️</span>
            <span>{{ course.duration || 'Chưa có' }}</span>
          </div>
          <div class="flex items-center text-sm text-gray-600">
            <span class="mr-2">💰</span>
            <span>{{ course.tuition_fee ? formatCurrency(course.tuition_fee) : 'Chưa có' }}</span>
          </div>
          <div v-if="course.target_student" class="text-sm text-gray-600">
            <span class="font-medium">Đối tượng:</span> {{ course.target_student }}
          </div>
        </div>

        <div v-if="isAdmin" class="flex gap-2 pt-4 border-t">
          <button
            @click="editCourse(course)"
            class="flex-1 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition"
          >
            Sửa
          </button>
          <button
            @click="deleteCourse(course.id)"
            class="flex-1 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition"
          >
            Xóa
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showCreateModal || editingCourse"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">
          {{ editingCourse ? 'Sửa khoá học' : 'Tạo khoá học mới' }}
        </h2>
        <form @submit.prevent="saveCourse" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên khoá học *</label>
            <input
              v-model="courseForm.name"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea
              v-model="courseForm.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            ></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Thời lượng</label>
              <input
                v-model="courseForm.duration"
                type="text"
                placeholder="VD: 6 tháng"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Học phí (VNĐ)</label>
              <input
                v-model="courseForm.tuition_fee"
                type="number"
                step="1000"
                min="0"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Đối tượng học viên</label>
            <textarea
              v-model="courseForm.target_student"
              rows="2"
              placeholder="VD: Sinh viên, người đi làm muốn chuyển nghề"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            ></textarea>
          </div>
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ saving ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/utils/axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();

const loading = ref(true);
const courses = ref([]);
const showCreateModal = ref(false);
const editingCourse = ref(null);
const saving = ref(false);
const courseForm = ref({
  name: '',
  description: '',
  duration: '',
  tuition_fee: '',
  target_student: ''
});

const isAdmin = computed(() => authStore.user?.role === 'admin');

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
};

const loadCourses = async () => {
    loading.value = true;
    try {
        const response = await api.get('/courses');

        // console.log('Courses loaded:', response.data);
        courses.value = response.data;

    } catch (error) {
        console.error('Error loading courses:', error);
    } finally {
        loading.value = false;
    }
};

const editCourse = (course) => {
  editingCourse.value = course;
  courseForm.value = {
    name: course.name,
    description: course.description || '',
    duration: course.duration || '',
    tuition_fee: course.tuition_fee || '',
    target_student: course.target_student || ''
  };
};

const closeModal = () => {
  showCreateModal.value = false;
  editingCourse.value = null;
  courseForm.value = {
    name: '',
    description: '',
    duration: '',
    tuition_fee: '',
    target_student: ''
  };
};

const saveCourse = async () => {
  saving.value = true;
  try {
    if (editingCourse.value) {
      await api.patch(`/courses/${editingCourse.value.id}`, courseForm.value);
    } else {
      await api.post('/courses', courseForm.value);
    }
    closeModal();
    await loadCourses();
  } catch (error) {
    console.error('Error saving course:', error);
    alert('Có lỗi xảy ra khi lưu khoá học');
  } finally {
    saving.value = false;
  }
};

const deleteCourse = async (id) => {
  if (!confirm('Bạn có chắc chắn muốn xóa khoá học này?')) return;

  try {
    await api.delete(`/courses/${id}`);
    await loadCourses();
  } catch (error) {
    console.error('Error deleting course:', error);
    alert('Có lỗi xảy ra khi xóa khoá học');
  }
};

onMounted(() => {
  loadCourses();
});
</script>
