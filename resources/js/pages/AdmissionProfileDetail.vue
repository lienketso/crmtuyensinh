<template>
  <div>
    <div class="mb-6">
      <button
        @click="$router.push({ name: 'admission-profiles' })"
        class="mb-4 text-blue-600 hover:text-blue-800 inline-flex items-center gap-1.5"
      >
        <ArrowLeft class="w-5 h-5" />
        <span>Quay lại</span>
      </button>
      <h1 class="text-2xl font-bold text-gray-900">Cập nhật hồ sơ xét tuyển</h1>
      <p class="text-gray-600 mt-1">{{ profile?.lead?.name }} – {{ profile?.lead?.phone }}</p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="profile" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Số CMND/CCCD</label>
              <input
                v-model="form.identification_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Số định danh"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố</label>
              <input
                v-model="form.province"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="VD: Hà Nội"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Năm tốt nghiệp THPT</label>
              <input
                v-model.number="form.graduation_year"
                type="number"
                min="1990"
                max="2100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="VD: 2024"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Học lực</label>
              <select
                v-model="form.academic_level"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option value="">-- Chọn --</option>
                <option value="Giỏi">Giỏi</option>
                <option value="Khá">Khá</option>
                <option value="Trung bình">Trung bình</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Điểm TB THPT (GPA)</label>
              <input
                v-model.number="form.gpa"
                type="number"
                step="0.01"
                min="0"
                max="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="VD: 8.5"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Hình thức xét tuyển</label>
              <select
                v-model="form.admission_method"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option value="">-- Chọn --</option>
                <option value="học bạ">Học bạ</option>
                <option value="điểm thi">Điểm thi THPT</option>
                <option value="liên thông">Liên thông</option>
                <option value="tuyển thẳng">Tuyển thẳng</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái hồ sơ</label>
            <select
              v-model="form.document_status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            >
              <option value="not_registered">Chưa đăng ký</option>
              <option value="registered">Đã đăng ký xét tuyển</option>
              <option value="submitted">Đã nộp hồ sơ</option>
              <option value="need_more_docs">Hồ sơ cần bổ sung</option>
              <option value="valid">Hồ sơ hợp lệ</option>
              <option value="in_review">Đang xét tuyển</option>
              <option value="admitted">Trúng tuyển</option>
              <option value="confirmed">Xác nhận nhập học</option>
              <option value="enrolled">Đã nhập học</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Điểm các môn</label>
            <div class="space-y-2">
              <div
                v-for="(row, idx) in form.subject_scores"
                :key="idx"
                class="flex flex-wrap items-center gap-2"
              >
                <input
                  v-model="row.subject"
                  type="text"
                  class="flex-1 min-w-[140px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                  placeholder="Tên môn (vd: Toán)"
                />
                <input
                  v-model.number="row.score"
                  type="number"
                  step="0.01"
                  min="0"
                  max="10"
                  class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                  placeholder="Điểm"
                />
                <button
                  type="button"
                  class="inline-flex items-center justify-center px-2 py-1 rounded-lg border border-red-300 text-xs text-red-600 hover:bg-red-50"
                  @click="removeSubjectRow(idx)"
                  v-if="form.subject_scores.length > 1"
                >
                  <XIcon class="w-3 h-3 mr-1" />
                  Xóa
                </button>
              </div>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50"
                @click="addSubjectRow"
              >
                <Plus class="w-3 h-3" />
                Thêm môn
              </button>
              <p class="mt-1 text-xs text-gray-500">
                Các môn và điểm sẽ được lưu trong trường <code>academic_records</code>.
              </p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú cán bộ</label>
            <textarea
              v-model="form.admin_note"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="Ghi chú khi duyệt hồ sơ..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">File hồ sơ</label>
            <div class="flex items-center gap-4 flex-wrap">
              <div v-if="profile.admission_file_url" class="flex items-center gap-2">
                <a
                  :href="profile.admission_file_url"
                  target="_blank"
                  class="text-blue-600 hover:underline"
                >
                  Xem file hiện tại
                </a>
              </div>
              <input
                ref="fileInput"
                type="file"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                class="text-sm"
                @change="onFileChange"
              />
              <p class="text-xs text-gray-500 w-full">PDF, DOC, DOCX, JPG, PNG. Tối đa 10MB.</p>
            </div>
          </div>

          <div v-if="flash.message" class="rounded-lg border px-4 py-3 text-sm"
            :class="flash.type === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'">
            {{ flash.message }}
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
            </button>
            <button
              type="button"
              @click="$router.push({ name: 'admission-profiles' })"
              class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              <XIcon class="w-4 h-4" />
              <span>Hủy</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/utils/axios';
import { ArrowLeft, Save, X as XIcon, Plus } from 'lucide-vue-next';

const route = useRoute();
const profile = ref(null);
const loading = ref(true);
const saving = ref(false);
const fileInput = ref(null);
const newFile = ref(null);
const flash = ref({ type: '', message: '' });

const form = reactive({
  identification_number: '',
  province: '',
  graduation_year: null,
  academic_level: '',
  gpa: null,
  admission_method: '',
  document_status: 'registered',
  admin_note: '',
  subject_scores: [
    { subject: '', score: null },
  ],
});

const setFlash = (type, message) => {
  flash.value = { type, message };
  setTimeout(() => { flash.value = { type: '', message: '' }; }, 3500);
};

const loadProfile = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/admission-profiles/${route.params.id}`);
    profile.value = res.data;
    form.identification_number = profile.value.identification_number ?? '';
    form.province = profile.value.province ?? '';
    form.graduation_year = profile.value.graduation_year ?? null;
    form.academic_level = profile.value.academic_level ?? '';
    form.gpa = profile.value.gpa != null ? Number(profile.value.gpa) : null;
    form.admission_method = profile.value.admission_method ?? '';
    form.document_status = profile.value.document_status ?? 'registered';
    form.admin_note = profile.value.admin_note ?? '';

    if (Array.isArray(profile.value.academic_records) && profile.value.academic_records.length > 0) {
      form.subject_scores = profile.value.academic_records.map((item) => ({
        subject: item.subject ?? item.name ?? '',
        score: item.score != null ? Number(item.score) : null,
      }));
    } else {
      form.subject_scores = [{ subject: '', score: null }];
    }
  } catch (e) {
    console.error(e);
    setFlash('error', 'Không tải được hồ sơ.');
  } finally {
    loading.value = false;
  }
};

const onFileChange = (e) => {
  newFile.value = e.target.files?.[0] || null;
};

const addSubjectRow = () => {
  form.subject_scores.push({ subject: '', score: null });
};

const removeSubjectRow = (idx) => {
  if (form.subject_scores.length <= 1) return;
  form.subject_scores.splice(idx, 1);
};

const submit = async () => {
  saving.value = true;
  try {
    const academicRecords = form.subject_scores
      .filter((row) => row.subject && row.score !== null && row.score !== '')
      .map((row) => ({
        subject: row.subject,
        score: Number(row.score),
      }));

    const fd = new FormData();
    fd.append('_method', 'PUT');
    fd.append('identification_number', form.identification_number);
    fd.append('province', form.province);
    if (form.graduation_year != null && form.graduation_year !== '') fd.append('graduation_year', form.graduation_year);
    fd.append('academic_level', form.academic_level);
    if (form.gpa != null && form.gpa !== '') fd.append('gpa', form.gpa);
    fd.append('admission_method', form.admission_method);
    fd.append('document_status', form.document_status);
    fd.append('admin_note', form.admin_note);
    if (academicRecords.length > 0) {
      fd.append('academic_records', JSON.stringify(academicRecords));
    }
    if (newFile.value) fd.append('admission_file', newFile.value);

    const res = await api.post(`/admission-profiles/${route.params.id}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    profile.value = res.data.profile ?? res.data;
    if (profile.value.admission_file && !profile.value.admission_file_url) {
      profile.value.admission_file_url = profile.value.admission_file;
    }
    setFlash('success', 'Đã cập nhật hồ sơ.');
    newFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
  } catch (e) {
    const msg = e?.response?.data?.message || e?.response?.data?.errors?.admission_file?.[0] || 'Cập nhật thất bại.';
    setFlash('error', msg);
  } finally {
    saving.value = false;
  }
};

onMounted(loadProfile);
</script>
