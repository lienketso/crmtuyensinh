# AI Sale cho Trường học / Giáo dục

Hệ thống AI Admission / AI Sale tool hỗ trợ tư vấn tuyển sinh tự động, chuẩn hoá kịch bản sale cho nhân viên, và thu lead theo dõi trạng thái nhập học.

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue.js (Dashboard) + Tailwind CSS
- **Database**: MySQL
- **API**: RESTful API với Laravel Sanctum
- **AI**: OpenAI API

## Tính năng chính

- ✅ AI Chat tư vấn tuyển sinh tự động
- ✅ Quản lý Lead (thu thập, theo dõi trạng thái)
- ✅ Quản lý khoá học
- ✅ Multi-tenant (mỗi trường = 1 tenant)
- ✅ Dashboard xem hội thoại và quản lý leads
- ✅ Phân quyền: Admin và Advisor

## Cài đặt (chạy trực tiếp, không Docker)

### Yêu cầu

- PHP >= 8.2
- Composer
- MySQL
- Node.js & NPM

### Bước 1: Clone và cài đặt dependencies

```bash
composer install
npm install
```

### Bước 2: Cấu hình môi trường

Copy file `.env.example` thành `.env` và cập nhật các thông tin:

```bash
cp .env.example .env
php artisan key:generate
```

Cập nhật các biến môi trường trong `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuyensinh
DB_USERNAME=root
DB_PASSWORD=

OPENAI_API_KEY=your_openai_api_key_here
```

### Bước 3: Chạy migrations

```bash
php artisan migrate
```

### Bước 4: Tạo dữ liệu mẫu (tùy chọn)

```bash
php artisan tinker
```

Trong tinker, tạo user mẫu:

```php
$user = App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
```

### Bước 5: Chạy server

```bash
php artisan serve
```

## Chạy bằng Docker

### Yêu cầu

- Docker & Docker Compose

### Bước 1: Chuẩn bị môi trường

```bash
cp .env.docker.example .env
php artisan key:generate
```

Bạn có thể chỉnh lại DB, mail, API key… trong `.env` nếu cần.

### Bước 2: Khởi động containers

```bash
docker compose up -d --build
```

Các service:

- **app**: PHP-FPM (Laravel, chạy trong `/var/www`)
- **web**: Nginx, expose `http://localhost:8080`
- **db**: MySQL 8, port host `3307`
- **node**: Vite dev server, port `5173`

### Bước 3: Migrate & seed trong container

```bash
docker compose exec app php artisan migrate --seed
```

### Bước 4: Truy cập

- Ứng dụng: `http://localhost:8080`
- API base: `http://localhost:8080/api`

## API Endpoints

### Public Endpoints

- `POST /api/chat` - Chat với AI (không cần auth)

### Auth Endpoints

- `POST /api/auth/login` - Đăng nhập
- `POST /api/auth/logout` - Đăng xuất (cần auth)
- `GET /api/auth/me` - Lấy thông tin user hiện tại (cần auth)

### Protected Endpoints (cần auth)

#### Leads
- `GET /api/leads` - Danh sách leads
- `POST /api/leads` - Tạo lead mới
- `GET /api/leads/{id}` - Chi tiết lead
- `PATCH /api/leads/{id}` - Cập nhật lead
- `PATCH /api/leads/{id}/status` - Cập nhật trạng thái lead

#### Courses
- `GET /api/courses` - Danh sách courses
- `POST /api/courses` - Tạo course (chỉ admin)
- `PATCH /api/courses/{id}` - Cập nhật course (chỉ admin)
- `DELETE /api/courses/{id}` - Xóa course (chỉ admin)

## Cấu trúc Database

- `users` - Người dùng hệ thống (admin, advisor)
- `courses` - Khoá học
- `leads` - Lead từ khách hàng
- `conversations` - Cuộc hội thoại
- `messages` - Tin nhắn trong hội thoại

## Phân quyền

- **admin**: Quản lý hệ thống, khoá học, prompt AI
- **advisor**: Xem lead & hội thoại

## License

MIT License
# crmtuyensinh
