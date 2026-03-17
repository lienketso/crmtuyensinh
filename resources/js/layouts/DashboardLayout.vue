<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar Desktop -->
    <aside class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col bg-white border-r border-gray-200">
      <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
        <div class="flex items-center flex-shrink-0 px-4 mb-8">
          <h1 class="text-2xl font-bold text-blue-600">CRM Tuyển Sinh</h1>
        </div>
        <nav class="flex-1 px-4 space-y-2">
          <router-link
            v-for="item in menuItems"
            :key="item.name"
            :to="{ name: item.route }"
            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition"
            :class="isActive(item.route)
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-700 hover:bg-gray-100'"
          >
            <component
              :is="item.icon"
              class="w-5 h-5 mr-3 flex-shrink-0"
            />
            <span class="flex-1">{{ item.name }}</span>
            <span
              v-if="item.route === 'tasks' && pendingTasksCount > 0"
              class="min-w-[1.25rem] px-1.5 py-0.5 text-xs font-semibold rounded-full bg-red-500 text-white text-center"
            >
              {{ pendingTasksCount > 99 ? '99+' : pendingTasksCount }}
            </span>
          </router-link>
        </nav>
        <div class="px-4 mt-auto pb-4">
          <div class="flex items-center px-4 py-3 bg-gray-50 rounded-lg">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
              <p class="text-xs text-gray-500">{{ authStore.user?.email }}</p>
            </div>
            <button
              @click="handleLogout"
              class="ml-2 text-gray-400 hover:text-gray-600"
              title="Đăng xuất"
            >
              <LogOut class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="md:pl-64">
      <!-- Top Bar Mobile -->
      <header class="md:hidden bg-white border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
          <h1 class="text-xl font-bold text-blue-600">AI Tuyển Sinh</h1>
          <button
            @click="showMobileMenu = !showMobileMenu"
            class="p-2 rounded-lg hover:bg-gray-100"
          >
            <Menu class="w-6 h-6" />
          </button>
        </div>
      </header>

      <!-- Mobile Menu -->
      <div
        v-if="showMobileMenu"
        class="md:hidden fixed inset-0 z-50 bg-white"
      >
        <div class="flex flex-col h-full">
          <div class="flex items-center justify-between p-4 border-b">
            <h2 class="text-xl font-bold text-blue-600">Menu</h2>
            <button @click="showMobileMenu = false" class="p-2">
              <X class="w-6 h-6" />
            </button>
          </div>
          <nav class="flex-1 px-4 py-4 space-y-2">
            <router-link
              v-for="item in menuItems"
              :key="item.name"
              :to="{ name: item.route }"
              @click="showMobileMenu = false"
              class="flex items-center px-4 py-3 text-base font-medium rounded-lg"
              :class="isActive(item.route)
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-100'"
            >
              <component
                :is="item.icon"
                class="w-5 h-5 mr-3 flex-shrink-0"
              />
              <span class="flex-1">{{ item.name }}</span>
              <span
                v-if="item.route === 'tasks' && pendingTasksCount > 0"
                class="min-w-[1.25rem] px-1.5 py-0.5 text-xs font-semibold rounded-full bg-red-500 text-white text-center"
              >
                {{ pendingTasksCount > 99 ? '99+' : pendingTasksCount }}
              </span>
            </router-link>
          </nav>
          <div class="p-4 border-t">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-lg">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                <p class="text-xs text-gray-500">{{ authStore.user?.email }}</p>
              </div>
              <button @click="handleLogout" class="text-gray-400 hover:text-gray-600">
                <LogOut class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <main class="py-6 px-4 md:px-8">
        <router-view />
      </main>

      <!-- Bottom Nav Mobile -->
      <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-2 py-2 safe-area-bottom">
        <div class="flex justify-around">
          <router-link
            v-for="item in mobileMenuItems"
            :key="item.name"
            :to="{ name: item.route }"
            class="relative flex flex-col items-center px-4 py-2 rounded-lg transition"
            :class="isActive(item.route)
              ? 'text-blue-600'
              : 'text-gray-500'"
          >
            <component
              :is="item.icon"
              class="w-5 h-5 mb-1"
            />
            <span class="text-xs">{{ item.name }}</span>
            <span
              v-if="item.route === 'tasks' && pendingTasksCount > 0"
              class="absolute top-1 right-2 min-w-[1.25rem] px-1.5 py-0.5 text-xs font-semibold rounded-full bg-red-500 text-white text-center"
            >
              {{ pendingTasksCount > 99 ? '99+' : pendingTasksCount }}
            </span>
          </router-link>
        </div>
      </nav>
    </div>

    <!-- Chat Widget -->
   
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/utils/axios';
import { useAuthStore } from '../stores/auth';
import ChatWidget from '../components/ChatWidget.vue';
import {
  LayoutDashboard,
  Users,
  ListTodo,
  FileText,
  User,
  UserCog,
  Settings,
  LogOut,
  Menu,
  X,
} from 'lucide-vue-next';

const route = useRoute();
const authStore = useAuthStore();

const showMobileMenu = ref(false);
const pendingTasksCount = ref(0);

const fetchPendingTasksCount = async () => {
  try {
    const res = await api.get('/tasks-pending-count');
    pendingTasksCount.value = res.data?.count ?? 0;
  } catch {
    pendingTasksCount.value = 0;
  }
};

onMounted(() => {
  fetchPendingTasksCount();
});

const menuItems = computed(() => {
  const base = [
    { name: 'Dashboard', route: 'dashboard', icon: LayoutDashboard },
    { name: 'Ứng viên', route: 'leads', icon: Users },
    { name: 'Công việc', route: 'tasks', icon: ListTodo },
    { name: 'Hồ sơ xét tuyển', route: 'admission-profiles', icon: FileText },
    { name: 'Tài khoản', route: 'account', icon: User },
  ];

  if (authStore.user?.role === 'admin' || authStore.user?.role === 'super_admin') {
    base.push(
      { name: 'Người dùng', route: 'users', icon: UserCog },
      { name: 'Cấu hình', route: 'settings', icon: Settings },
    );
  }

  if (authStore.user?.role === 'super_admin') {
    base.push({ name: 'Trường học', route: 'schools', icon: Users });
  }

  return base;
});

const mobileMenuItems = computed(() => {
  const base = [
    { name: 'Trang chủ', route: 'dashboard', icon: LayoutDashboard },
    { name: 'Ứng viên', route: 'leads', icon: Users },
    { name: 'Công việc', route: 'tasks', icon: ListTodo },
    { name: 'Hồ sơ xét tuyển', route: 'admission-profiles', icon: FileText },
    { name: 'Tài khoản', route: 'account', icon: User },
  ];

  if (authStore.user?.role === 'admin' || authStore.user?.role === 'super_admin') {
    base.push(
      { name: 'User', route: 'users', icon: UserCog },
      { name: 'Cấu hình', route: 'settings', icon: Settings },
    );
  }

  if (authStore.user?.role === 'super_admin') {
    base.push({ name: 'Trường', route: 'schools', icon: Users });
  }

  return base;
});

const isActive = (routeName) => {
  return route.name === routeName;
};

const handleLogout = async () => {
  try {
    await api.post('/auth/logout');
  } catch (err) {
    console.error('Logout error:', err);
  } finally {
    authStore.logout();
  }
};
</script>

<style scoped>
.safe-area-bottom {
  padding-bottom: env(safe-area-inset-bottom);
}
</style>
