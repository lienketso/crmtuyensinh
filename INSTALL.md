# Hướng dẫn cài đặt chi tiết

## 1. Cài đặt Dependencies

```bash
composer install
npm install
```

**Lưu ý**: Nếu gặp lỗi về yêu cầu PHP version (ví dụ: yêu cầu PHP >= 8.4 nhưng bạn đang dùng PHP 8.2), bạn có thể chạy:

```bash
composer install --ignore-platform-reqs
```

Hoặc sử dụng `composer update --ignore-platform-reqs` khi cập nhật dependencies.

File `composer.json` đã được cấu hình với `platform.php: "8.2.4"` để tương thích với PHP 8.2, nhưng một số package có thể vẫn yêu cầu phiên bản cao hơn.

## 2. Cấu hình Database

Tạo database MySQL:

```sql
CREATE DATABASE tuyensinh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cập nhật file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuyensinh
DB_USERNAME=root
DB_PASSWORD=your_password
```

## 3. Cấu hình OpenAI

Thêm vào file `.env`:

```env
OPENAI_API_KEY=sk-your-openai-api-key-here
```

Lấy API key tại: https://platform.openai.com/api-keys

## 4. Chạy Migrations

```bash
php artisan migrate
```

## 5. Tạo dữ liệu mẫu

Sử dụng Tinker để tạo school và user đầu tiên:

```bash
php artisan tinker
```

```php
// Tạo admin user
$admin = App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
    'role' => 'admin'
]);

// Tạo advisor user
$advisor = App\Models\User::create([
    'name' => 'Nhân viên tư vấn',
    'email' => 'advisor@example.com',
    'password' => bcrypt('password123'),
    'role' => 'advisor'
]);

// Tạo course mẫu
$course = App\Models\Course::create([
    'name' => 'Khoá học Lập trình Web',
    'description' => 'Học lập trình web từ cơ bản đến nâng cao',
    'duration' => '6 tháng',
    'tuition_fee' => 5000000,
    'target_student' => 'Sinh viên, người đi làm muốn chuyển nghề'
]);
```

## 6. Chạy ứng dụng

```bash
php artisan serve
```

Truy cập: http://localhost:8000

## 7. Test API

### Đăng nhập

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

### Chat với AI (public)

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Học phí khoá lập trình web bao nhiêu?"
  }'
```

### Lấy danh sách leads (cần token)

```bash
curl -X GET http://localhost:8000/api/leads \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Lưu ý

- Đảm bảo đã cấu hình đúng OpenAI API key để AI chat hoạt động
- Hệ thống dùng nội bộ cho 1 trường (single-tenant)
- Admin có quyền quản lý courses, advisor chỉ xem leads và conversations
