<template>
  <div>
    <div class="mb-6">
      <button
        @click="$router.back()"
        class="mb-4 text-blue-600 hover:text-blue-800 inline-flex items-center gap-1.5"
      >
        <ArrowLeft class="w-5 h-5" />
        <span>Quay lại</span>
      </button>

      <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Chi tiết Lead</h1>
        <div class="inline-flex items-center gap-2">
          <button
            v-if="lead && !lead.admission_profile"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!lead?.id || creatingProfile"
            @click="goCreateProfile()"
          >
            <FilePlus2 class="w-4 h-4" />
            <span>{{ creatingProfile ? 'Đang tạo...' : 'Tạo hồ sơ' }}</span>
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!isAdmin || !lead?.id"
            @click="goCreateTask()"
          >
            <ListTodo class="w-4 h-4" />
            <span>Tạo task</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="lead" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Lead Info -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin</h2>
          <div class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Tên</label>
              <p class="text-gray-900 font-medium">{{ lead.name || 'Chưa có' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Số điện thoại</label>
              <p class="text-gray-900">{{ lead.phone || 'Chưa có' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Email</label>
              <p class="text-gray-900">{{ lead.email || 'Chưa có' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Ngành học quan tâm</label>
              <p class="text-gray-900">{{ lead.interest_course || 'Chưa có' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Nguồn</label>
              <span class="inline-block px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                {{ getSourceLabel(lead.source) }}
              </span>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Trạng thái</label>
              <select
                :value="lead.status"
                @change="updateStatus($event.target.value)"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option value="new">Mới</option>
                <option value="contacted">Đã liên hệ</option>
                <option value="considering">Đang xem xét</option>
                <option value="enrolled">Đã đăng ký</option>
                <option value="lost">Mất</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Ngày tạo</label>
              <p class="text-gray-900">{{ formatDate(lead.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Conversations -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Lịch sử hội thoại</h2>
          <div v-if="conversations.length === 0" class="text-center py-8 text-gray-500">
            Chưa có hội thoại nào
          </div>
          <div v-else class="space-y-6">
            <div
              v-for="conversation in conversations"
              :key="conversation.id"
              class="border-l-4 border-blue-500 pl-4"
            >
              <div class="mb-2">
                <span class="text-sm text-gray-500">
                  {{ formatDate(conversation.created_at) }} - {{ getChannelLabel(conversation.channel) }}
                </span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="message in conversation.messages"
                  :key="message.id"
                  class="flex"
                  :class="message.sender === 'lead' ? 'justify-start' : 'justify-end'"
                >
                  <div
                    class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                    :class="message.sender === 'lead'
                      ? 'bg-gray-100 text-gray-900'
                      : 'bg-blue-600 text-white'"
                  >
                    <p class="text-sm">{{ message.content }}</p>
                    <p class="text-xs mt-1 opacity-75">
                      {{ formatTime(message.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/utils/axios';
import { useAuthStore } from '@/stores/auth';
import { ArrowLeft, ListTodo, FilePlus2 } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const isAdmin = computed(() => authStore.user?.role === 'admin');

const loading = ref(true);
const lead = ref(null);
const conversations = ref([]);
const creatingProfile = ref(false);

const getSourceLabel = (source) => {
  const labels = {
    website: 'Website',
    facebook: 'Facebook',
    zalo: 'Zalo',
    manual: 'Nhập thủ công'
  };
  return labels[source] || source;
};

const getChannelLabel = (channel) => {
  const labels = {
    web: 'Website',
    facebook: 'Facebook',
    zalo: 'Zalo'
  };
  return labels[channel] || channel;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN');
};

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const loadLead = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/leads/${route.params.id}/show`);
    console.log(response.data)
    lead.value = response.data;

    // Load conversations
    if (lead.value.conversations) {
      conversations.value = lead.value.conversations;
    }
  } catch (error) {
    console.error('Error loading lead:', error);
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (status) => {
  try {
    await api.post(`/leads/${route.params.id}/status`, { status });
    lead.value.status = status;
  } catch (error) {
    console.error('Error updating status:', error);
    alert('Có lỗi xảy ra khi cập nhật trạng thái');
  }
};

const goCreateTask = () => {
  if (!lead.value?.id) return;
  router.push({ name: 'tasks', query: { lead_id: lead.value.id, open: '1' } });
};

const goCreateProfile = async () => {
  if (!lead.value?.id) return;
  creatingProfile.value = true;
  try {
    await api.post(`/leads/${lead.value.id}/admission-profile`, {
      identification_number: null,
      academic_records: [],
    });
    lead.value.status = 'considering';
    alert('Đã tạo hồ sơ xét tuyển thành công.');
    router.push({ name: 'admission-profiles' });
  } catch (err) {
    const msg = err?.response?.data?.message || 'Tạo hồ sơ thất bại.';
    alert(msg);
  } finally {
    creatingProfile.value = false;
  }
};

onMounted(() => {
  authStore.initialize?.();
  loadLead();
});
</script>
