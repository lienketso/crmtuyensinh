import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/login-admin',
        name: 'login-admin', // Tên route đúng
        component: () => import('../pages/Login.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/',
        component: () => import('../layouts/DashboardLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../pages/Dashboard.vue')
            },
            {
                path: 'leads',
                name: 'leads',
                component: () => import('../pages/Leads.vue')
            },
            {
                path: 'leads/:id',
                name: 'lead-detail',
                component: () => import('../pages/LeadDetail.vue')
            },
            {
                path: 'tasks',
                name: 'tasks',
                component: () => import('../pages/Task.vue')
            },
            {
                path: 'admission-profiles',
                name: 'admission-profiles',
                component: () => import('../pages/AdmissionProfiles.vue')
            },
            {
                path: 'admission-profiles/:id',
                name: 'admission-profile-detail',
                component: () => import('../pages/AdmissionProfileDetail.vue')
            },
            {
                path: 'users',
                name: 'users',
                component: () => import('../pages/Users.vue')
            },
            {
                path: 'schools',
                name: 'schools',
                component: () => import('../pages/Schools.vue')
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('../pages/Settings.vue')
            },
            {
                path: 'account',
                name: 'account',
                component: () => import('../pages/Account.vue')
            }
        ]
    },
    // Thêm catch-all route cho 404
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

// Hàm kiểm tra authentication độc lập
const isAuthenticated = () => {
    const token = localStorage.getItem('access_token') ||
        sessionStorage.getItem('access_token');
    const user = localStorage.getItem('user') ||
        sessionStorage.getItem('user');

    return !!token && !!user;
};

// Get base path - works for both root and subdirectory installations
const getBasePath = () => {
    // Try to get from Laravel first
    if (window.APP_BASE_PATH) {
        try {
            const url = new URL(window.APP_BASE_PATH);
            let base = url.pathname;
            // Remove trailing slash
            base = base.replace(/\/$/, '');
            // If it's just '/', return '/', otherwise return the path
            return base || '/';
        } catch (e) {
            // If URL parsing fails, try to extract from string
            const match = window.APP_BASE_PATH.match(/https?:\/\/[^\/]+(\/.*)/);
            if (match && match[1]) {
                return match[1].replace(/\/$/, '') || '/';
            }
        }
    }

    // Auto-detect from current location
    const pathname = window.location.pathname;

    // If we're at root, return '/'
    if (pathname === '/' || pathname === '') {
        return '/';
    }

    // For XAMPP: if pathname is /tuyensinh/public/ or /tuyensinh/public
    // we want base to be /tuyensinh/public
    let path = pathname.replace(/\/$/, ''); // Remove trailing slash

    // If path contains '/public', use everything up to and including '/public'
    if (path.includes('/public')) {
        const publicIndex = path.indexOf('/public');
        return path.substring(0, publicIndex + '/public'.length);
    }

    // For other subdirectories, use the first segment
    // e.g., /tuyensinh -> /tuyensinh
    const firstSegment = path.split('/').filter(s => s)[0];
    return firstSegment ? '/' + firstSegment : '/';
};

const basePath = getBasePath();

const router = createRouter({
    history: createWebHistory(basePath),
    routes
});

// Navigation guard
router.beforeEach((to, from, next) => {

    // Kiểm tra authentication
    const isAuth = isAuthenticated();

    // Nếu route yêu cầu auth nhưng chưa đăng nhập
    if (to.meta.requiresAuth && !isAuth) {
        next({ name: 'login-admin' }); // Sửa tên route đúng
        return;
    }

    // Nếu route yêu cầu guest (chỉ cho chưa đăng nhập) nhưng đã đăng nhập
    if (to.meta.requiresGuest && isAuth) {
        console.log('Redirect to dashboard: Requires guest but already authenticated');
        next({ name: 'dashboard' });
        return;
    }

    next();
});

export default router;
