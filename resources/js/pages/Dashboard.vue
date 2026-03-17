<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600 mt-1">Tổng quan hệ thống</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Tổng ứng viên</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalLeads }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <Users class="w-6 h-6 text-blue-600" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ứng viên mới</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.newLeads }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <UserPlus class="w-6 h-6 text-green-600" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Đã đăng ký</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.enrolled }}</p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <CheckCircle2 class="w-6 h-6 text-purple-600" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ngành học</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalCourses }}</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-3">
                        <BookOpen class="w-6 h-6 text-orange-500" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ứng viên gần đây</h2>
                <div v-if="loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
                <div v-else-if="recentLeads.length === 0" class="text-center py-8 text-gray-500">
                    Chưa có leads nào
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="lead in recentLeads"
                        :key="lead.id"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
                        @click="$router.push({ name: 'lead-detail', params: { id: lead.id } })"
                    >
                        <div>
                            <p class="font-medium text-gray-900">{{ lead.name || 'Chưa có tên' }}</p>
                            <p class="text-sm text-gray-500">{{ lead.phone || lead.email || 'Chưa có thông tin' }}</p>
                        </div>
                        <span
                            class="px-2 py-1 text-xs font-medium rounded"
                            :class="{
                'bg-blue-100 text-blue-800': lead.status === 'new',
                'bg-yellow-100 text-yellow-800': lead.status === 'contacted',
                'bg-purple-100 text-purple-800': lead.status === 'considering',
                'bg-green-100 text-green-800': lead.status === 'enrolled'
              }"
                        >
              {{ getStatusLabel(lead.status) }}
            </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Thống kê theo nguồn</h2>
                <div v-if="loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
                <div v-else-if="Object.keys(sourceStats).length === 0" class="text-center py-8 text-gray-500">
                    Chưa có dữ liệu thống kê
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(count, source) in sourceStats"
                        :key="source"
                        class="flex items-center justify-between"
                    >
                        <span class="text-gray-700">{{ getSourceLabel(source) }}</span>
                        <span class="font-semibold text-gray-900">{{ count }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error message -->
        <div v-if="error" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/axios';
import { Users, UserPlus, CheckCircle2, BookOpen } from 'lucide-vue-next';

const router = useRouter();
const loading = ref(true);
const error = ref('');
const stats = ref({
    totalLeads: 0,
    newLeads: 0,
    enrolled: 0,
    totalCourses: 0
});
const recentLeads = ref([]);
const sourceStats = ref({});

const getStatusLabel = (status) => {
    const labels = {
        new: 'Mới',
        contacted: 'Đã liên hệ',
        considering: 'Đang xem xét',
        enrolled: 'Đã đăng ký'
    };
    return labels[status] || status;
};

const getSourceLabel = (source) => {
    const labels = {
        website: 'Website',
        facebook: 'Facebook',
        zalo: 'Zalo',
        manual: 'Nhập thủ công',
        tiktok: 'TikTok',
        google: 'Google'
    };
    return labels[source] || source;
};

// Helper function để đảm bảo data là array
const ensureArray = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (data.data && Array.isArray(data.data)) return data.data;
    if (typeof data === 'object') return [data];
    return [];
};

// Helper function để filter
const safeFilter = (array, filterFn) => {
    if (!Array.isArray(array)) return [];
    return array.filter(filterFn);
};

const loadDashboard = async () => {
    loading.value = true;
    error.value = '';

    try {

        const [leadsRes, coursesRes] = await Promise.all([
            api.get('/leads-list', { params: { per_page: 500 } }),
            api.get('/course-list')
        ]);

        const leads = ensureArray(leadsRes.data?.data || leadsRes.data);
        const totalLeads = leadsRes.data?.total ?? leads.length;

        const courses = ensureArray(coursesRes.data);

        // Tính toán stats
        stats.value.totalLeads = totalLeads;
        stats.value.newLeads = safeFilter(leads, l => l.status === 'new').length;
        stats.value.enrolled = safeFilter(leads, l => l.status === 'enrolled').length;
        stats.value.totalCourses = courses.length;

        // Lấy 5 leads gần đây (giả sử có id để sort)
        recentLeads.value = leads
            .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
            .slice(0, 5);

        // Tính thống kê theo nguồn
        sourceStats.value = leads.reduce((acc, lead) => {
            const source = lead.source || 'unknown';
            acc[source] = (acc[source] || 0) + 1;
            return acc;
        }, {});


    } catch (err) {
        console.error('Error loading dashboard:', err);

        if (err.response?.status === 401) {
            error.value = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
            // Có thể redirect đến login ở đây
            router.push({ name: 'login-admin' });
        } else if (err.response?.status === 404) {
            error.value = 'API endpoint không tồn tại. Vui lòng kiểm tra route.';
        } else {
            error.value = `Có lỗi xảy ra: ${err.message || 'Không thể tải dữ liệu'}`;
        }

        // Set default empty arrays để tránh lỗi template
        recentLeads.value = [];
        sourceStats.value = {};

    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadDashboard();
});
</script>
