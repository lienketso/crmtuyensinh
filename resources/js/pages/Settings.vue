<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Cấu hình hệ thống</h1>
      <p class="text-gray-600 mt-1">Thay đổi logo, favicon và cấu hình email SMTP cho CRM.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Thông tin chung -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin chung</h2>
        <form class="space-y-4" @submit.prevent="saveGeneral">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên hệ thống</label>
            <input
              v-model="form.site_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="VD: AI Tuyển Sinh"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
              <div class="flex items-center gap-4">
                <div
                  v-if="preview.logo || settings.logo_url"
                  class="w-16 h-16 rounded-md border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center"
                >
                  <img
                    :src="preview.logo || settings.logo_url"
                    alt="Logo"
                    class="max-w-full max-h-full object-contain"
                  />
                </div>
                <div class="flex-1">
                  <input
                    type="file"
                    accept="image/*"
                    @change="onFileChange($event, 'logo')"
                    class="w-full text-sm"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    Hỗ trợ: JPG, PNG, SVG, WEBP. Tối đa 2MB.
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
              <div class="flex items-center gap-4">
                <div
                  v-if="preview.favicon || settings.favicon_url"
                  class="w-10 h-10 rounded-md border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center"
                >
                  <img
                    :src="preview.favicon || settings.favicon_url"
                    alt="Favicon"
                    class="max-w-full max-h-full object-contain"
                  />
                </div>
                <div class="flex-1">
                  <input
                    type="file"
                    accept=".ico,image/*"
                    @change="onFileChange($event, 'favicon')"
                    class="w-full text-sm"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    Hỗ trợ: ICO, PNG, JPG, SVG. Tối đa 1MB.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email nhận thông báo lead mới
            </label>
            <input
              v-model="form.mail_lead_recipient"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="vd: leads-notify@example.com"
            />
            <p class="mt-1 text-xs text-gray-500">
              Nếu bỏ trống, hệ thống sẽ dùng địa chỉ "Từ email" ở trên ({{ form.mail_from_address || 'chưa cấu hình' }}).
            </p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="saving.general"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ saving.general ? 'Đang lưu...' : 'Lưu thông tin chung' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Cấu hình Mail SMTP -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Cấu hình Email (SMTP)</h2>
        <form class="space-y-4" @submit.prevent="saveMail">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mailer mặc định</label>
              <select
                v-model="form.mail_default"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option value="smtp">smtp</option>
                <option value="log">log</option>
                <option value="sendmail">sendmail</option>
                <option value="failover">failover</option>
                <option value="roundrobin">roundrobin</option>
                <option value="array">array</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Từ email</label>
              <input
                v-model="form.mail_from_address"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="no-reply@example.com"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên hiển thị</label>
            <input
              v-model="form.mail_from_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="CRM Tuyển sinh"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
              <input
                v-model="form.mail_host"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="smtp.example.com"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input
                v-model.number="form.mail_port"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="587"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
              <input
                v-model="form.mail_encryption"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="tls / ssl / null"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
              <input
                v-model="form.mail_username"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Password
              <span v-if="settings.mail_has_password" class="text-xs text-gray-500">
                (đang có mật khẩu, để trống nếu không muốn đổi)
              </span>
            </label>
            <input
              v-model="form.mail_password"
              type="password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              autocomplete="new-password"
            />
          </div>

          <div class="pt-2">
            <div class="flex flex-wrap items-center gap-3">
              <button
                type="submit"
                :disabled="saving.mail"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ saving.mail ? 'Đang lưu...' : 'Lưu cấu hình mail' }}
              </button>
              <button
                type="button"
                @click="testMail"
                :disabled="testingMail"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50"
              >
                {{ testingMail ? 'Đang gửi email test...' : 'Gửi email test' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '@/utils/axios';

const settings = reactive({
  site_name: '',
  logo_url: null,
  favicon_url: null,
  mail_has_password: false,
  mail_lead_recipient: '',
});

const form = reactive({
  site_name: '',
  mail_default: 'smtp',
  mail_from_name: '',
  mail_from_address: '',
  mail_host: '',
  mail_port: null,
  mail_encryption: '',
  mail_username: '',
  mail_password: '',
  mail_lead_recipient: '',
});

const files = reactive({
  logo: null,
  favicon: null,
});

const preview = reactive({
  logo: null,
  favicon: null,
});

const flash = ref({ type: '', message: '' });
const saving = reactive({
  general: false,
  mail: false,
});
const testingMail = ref(false);

const setFlash = (type, message) => {
  flash.value = { type, message };
  window.setTimeout(() => {
    if (flash.value.message === message) {
      flash.value = { type: '', message: '' };
    }
  }, 3500);
};

const loadSettings = async () => {
  try {
    const res = await api.get('/admin/settings');
    const data = res.data || {};

    settings.site_name = data.site_name || '';
    settings.logo_url = data.logo_url || null;
    settings.favicon_url = data.favicon_url || null;
    settings.mail_has_password = !!data.mail_has_password;
    settings.mail_lead_recipient = data.mail_lead_recipient || '';

    form.site_name = data.site_name || '';
    form.mail_default = data.mail_default || 'smtp';
    form.mail_from_name = data.mail_from_name || '';
    form.mail_from_address = data.mail_from_address || '';
    form.mail_host = data.mail_host || '';
    form.mail_port = data.mail_port || null;
    form.mail_encryption = data.mail_encryption || '';
    form.mail_username = data.mail_username || '';
    form.mail_password = '';
    form.mail_lead_recipient = data.mail_lead_recipient || '';
  } catch (e) {
    console.error('Error loading settings:', e);
    setFlash('error', 'Không thể tải cấu hình. Vui lòng thử lại.');
  }
};

const onFileChange = (event, field) => {
  const file = event.target.files?.[0];
  if (!file) return;
  files[field] = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    preview[field] = e.target?.result;
  };
  reader.readAsDataURL(file);
};

const buildFormData = (fields, includeFiles = false) => {
  const fd = new FormData();
  Object.entries(fields).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      fd.append(key, value);
    }
  });
  if (includeFiles) {
    if (files.logo) fd.append('logo', files.logo);
    if (files.favicon) fd.append('favicon', files.favicon);
  }
  return fd;
};

const saveGeneral = async () => {
  saving.general = true;
  try {
    const fd = buildFormData(
      {
        site_name: form.site_name,
      },
      true,
    );
    const res = await api.post('/admin/settings', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    const data = res.data || {};
    settings.site_name = data.site_name || settings.site_name;
    settings.logo_url = data.logo_url || settings.logo_url;
    settings.favicon_url = data.favicon_url || settings.favicon_url;
    setFlash('success', 'Đã lưu thông tin chung.');
  } catch (e) {
    console.error('Error saving general settings:', e);
    const msg =
      e?.response?.data?.message ||
      Object.values(e?.response?.data?.errors || {})[0]?.[0] ||
      'Lưu thông tin chung thất bại.';
    setFlash('error', msg);
  } finally {
    saving.general = false;
  }
};

const saveMail = async () => {
  saving.mail = true;
  try {
    const fd = buildFormData({
      mail_default: form.mail_default,
      mail_from_name: form.mail_from_name,
      mail_from_address: form.mail_from_address,
      mail_host: form.mail_host,
      mail_port: form.mail_port,
      mail_encryption: form.mail_encryption,
      mail_username: form.mail_username,
      mail_password: form.mail_password,
      mail_lead_recipient: form.mail_lead_recipient,
    });
    const res = await api.post('/admin/settings', fd);
    const data = res.data || {};
    settings.mail_has_password = !!data.mail_has_password;
    form.mail_password = '';
    setFlash('success', 'Đã lưu cấu hình mail.');
  } catch (e) {
    console.error('Error saving mail settings:', e);
    const msg =
      e?.response?.data?.message ||
      Object.values(e?.response?.data?.errors || {})[0]?.[0] ||
      'Lưu cấu hình mail thất bại.';
    setFlash('error', msg);
  } finally {
    saving.mail = false;
  }
};

const testMail = async () => {
  testingMail.value = true;
  try {
    const payload = {};
    // Cho phép override địa chỉ test nếu user điền mail_lead_recipient
    if (form.mail_lead_recipient) {
      payload.to = form.mail_lead_recipient;
    }
    const res = await api.post('/admin/settings/test-mail', payload);
    const msg = res.data?.message || 'Đã gửi email test thành công.';
    setFlash('success', msg);
  } catch (e) {
    console.error('Error sending test mail:', e);
    const msg =
      e?.response?.data?.message ||
      Object.values(e?.response?.data?.errors || {})[0]?.[0] ||
      'Gửi email test thất bại.';
    setFlash('error', msg);
  } finally {
    testingMail.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>

