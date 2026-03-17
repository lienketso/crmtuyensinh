import './bootstrap';
import '../css/app.css';
import { createPinia } from 'pinia'
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';


const app = createApp(App);
const pinia = createPinia()
app.use(router);
app.use(pinia)

// Kiểm tra và redirect nếu cần khi app khởi động
router.isReady().then(() => {
    const isAuthenticated = () => {
        const token = localStorage.getItem('access_token');
        const user = localStorage.getItem('user');
        return !!token && !!user;
    };

    const currentRoute = router.currentRoute.value;

    // Nếu đang ở trang cần auth nhưng chưa đăng nhập
    if (currentRoute.meta.requiresAuth && !isAuthenticated()) {
        console.log('App init: Redirecting to login');
        router.push({ name: 'login-admin' });
    }

    // Nếu đang ở login nhưng đã đăng nhập
    if (currentRoute.meta.requiresGuest && isAuthenticated()) {
        console.log('App init: Redirecting to dashboard');
        router.push({ name: 'dashboard' });
    }
});

app.mount('#app');
