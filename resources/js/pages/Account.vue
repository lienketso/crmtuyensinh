<template>
  <div class="max-w-4xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Tài khoản cá nhân</h1>
      <p class="text-gray-600 mt-1">Cập nhật thông tin và đổi mật khẩu</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Profile -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin cá nhân</h2>

        <div v-if="profileMsg" class="mb-4 p-3 rounded-lg text-sm" :class="profileMsg.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
          {{ profileMsg.text }}
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên</label>
            <input
              v-model="profileForm.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Nhập họ tên"
            />
            <p v-if="profileErrors.name" class="mt-1 text-xs text-red-600">{{ profileErrors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="profileForm.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Nhập email"
            />
            <p v-if="profileErrors.email" class="mt-1 text-xs text-red-600">{{ profileErrors.email }}</p>
          </div>

          <button
            :disabled="savingProfile"
            @click="saveProfile"
            class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <span v-if="savingProfile">Đang lưu...</span>
            <span v-else>Lưu thông tin</span>
          </button>
        </div>
      </div>

      <!-- Password -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Đổi mật khẩu</h2>

        <div v-if="passwordMsg" class="mb-4 p-3 rounded-lg text-sm" :class="passwordMsg.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
          {{ passwordMsg.text }}
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
            <input
              v-model="passwordForm.current_password"
              type="password"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Nhập mật khẩu hiện tại"
            />
            <p v-if="passwordErrors.current_password" class="mt-1 text-xs text-red-600">{{ passwordErrors.current_password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
            <input
              v-model="passwordForm.password"
              type="password"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Nhập mật khẩu mới"
            />
            <p v-if="passwordErrors.password" class="mt-1 text-xs text-red-600">{{ passwordErrors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nhập lại mật khẩu mới</label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Nhập lại mật khẩu mới"
            />
          </div>

          <button
            :disabled="savingPassword"
            @click="changePassword"
            class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-black transition disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <span v-if="savingPassword">Đang đổi...</span>
            <span v-else>Đổi mật khẩu</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/axios';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const profileForm = ref({ name: '', email: '' });
const profileErrors = ref({});
const profileMsg = ref(null);
const savingProfile = ref(false);

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const passwordErrors = ref({});
const passwordMsg = ref(null);
const savingPassword = ref(false);

const loadMe = async () => {
  try {
    const res = await api.get('/auth/me');
    const u = res.data;
    profileForm.value = {
      name: u?.name || '',
      email: u?.email || '',
    };
    authStore.setUser({
      id: u?.id,
      name: u?.name,
      email: u?.email,
      role: u?.role,
    });
  } catch (e) {
    // fallback: dùng data local nếu API lỗi
    profileForm.value = {
      name: authStore.user?.name || '',
      email: authStore.user?.email || '',
    };
  }
};

const saveProfile = async () => {
  savingProfile.value = true;
  profileErrors.value = {};
  profileMsg.value = null;
  try {
    const res = await api.put('/auth/me', profileForm.value);
    const u = res.data?.user || res.data;
    authStore.setUser({
      id: u?.id,
      name: u?.name,
      email: u?.email,
      role: u?.role,
    });
    profileMsg.value = { type: 'success', text: res.data?.message || 'Đã cập nhật thông tin.' };
  } catch (e) {
    const errors = e?.response?.data?.errors || {};
    profileErrors.value = {
      name: errors?.name?.[0],
      email: errors?.email?.[0],
    };
    profileMsg.value = { type: 'error', text: e?.response?.data?.message || 'Cập nhật thất bại.' };
  } finally {
    savingProfile.value = false;
  }
};

const changePassword = async () => {
  savingPassword.value = true;
  passwordErrors.value = {};
  passwordMsg.value = null;
  try {
    const res = await api.put('/auth/me/password', passwordForm.value);
    passwordMsg.value = { type: 'success', text: res.data?.message || 'Đổi mật khẩu thành công.' };
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
  } catch (e) {
    const errors = e?.response?.data?.errors || {};
    passwordErrors.value = {
      current_password: errors?.current_password?.[0],
      password: errors?.password?.[0],
    };
    passwordMsg.value = { type: 'error', text: e?.response?.data?.message || 'Đổi mật khẩu thất bại.' };
  } finally {
    savingPassword.value = false;
  }
};

onMounted(() => {
  authStore.initialize?.();
  loadMe();
});
</script>
