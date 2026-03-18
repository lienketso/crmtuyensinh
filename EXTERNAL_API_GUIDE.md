## Hướng dẫn tích hợp API tạo Lead & Hồ sơ xét tuyển (External)

Tài liệu này dành cho **hệ thống bên ngoài** muốn đẩy dữ liệu ứng viên (lead) và hồ sơ xét tuyển (admission profile) vào CRM.

Ký hiệu:
- **{BASE_URL}**: domain + path gốc của API, ví dụ:
  - `http://your-domain.com/api`
  - hoặc môi trường XAMPP: `http://localhost/tuyensinh/public/api`

---

## 1. Xác thực

Có hai cơ chế:

- **1) Bearer Token (Laravel Sanctum)** – dùng cho các API nội bộ CRM:
  - Đăng nhập lấy token: `POST {BASE_URL}/login`
  - Các API như tạo lead, tạo hồ sơ từ lead (authenticated)
- **2) Static API Key** – dùng cho endpoint external tạo hồ sơ:
  - Gửi header: `X-API-KEY: {YOUR_STATIC_API_KEY}`

Ngoài ra hệ thống có hỗ trợ **Bearer token mặc định cho tích hợp** (không cần login) nếu được cấu hình ở server:

- Header: `Authorization: Bearer {INTEGRATION_BEARER_TOKEN}`
- Token này dùng được cho một số endpoint tích hợp như:
  - `POST {BASE_URL}/lead-create`
  - `POST {BASE_URL}/admission-profiles/from-lead`
  - `GET {BASE_URL}/schools` *(không yêu cầu super admin)*

### 1.1. Đăng nhập lấy Bearer token

**Endpoint**

- `POST {BASE_URL}/login`

**Headers**

- `Content-Type: application/json`
- `Accept: application/json`

**Body**

```json
{
  "email": "admin@example.com",
  "password": "secret"
}
```

**Response thành công (rút gọn)**

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

**Bearer token** sẽ dùng trong header:

```http
Authorization: Bearer {access_token}
```

---

## 1.2. Xác thực Bearer token (khuyến nghị)

Đối với các endpoint tích hợp cần quyền truy cập dữ liệu (ví dụ: danh sách trường), nên dùng **Bearer token** lấy từ `POST {BASE_URL}/login`.

---

## 2. API tạo Lead (ứng viên)

### 2.1. Endpoint

- `POST {BASE_URL}/lead-create`
- Yêu cầu header **Bearer token**.

**Headers**

- `Authorization: Bearer {access_token}`
- `Content-Type: application/json`
- `Accept: application/json`

### 2.2. Body request

Các trường chính:

- `name` *(string, nullable)* – Họ tên ứng viên
- `phone` *(string, nullable)* – Số điện thoại
- `email` *(string, nullable, email)* – Email
- `interest_course` *(string, nullable)* – Khoá học quan tâm
- `source` *(string, nullable, enum)* – Nguồn: `website | facebook | zalo | manual`
  - Nếu bỏ trống sẽ mặc định là `manual`
- `assigned_to` *(int, nullable)* – ID user trong CRM mà lead sẽ được gán cho
- `school_id` *(int, **bắt buộc cho super admin**, nullable cho admin/advisor)* – ID trường mà lead thuộc về

Ngoài ra, hệ thống đã hỗ trợ **tạo Conversation + Message ban đầu** nếu bạn truyền thêm:

- `message` *(string, nullable)* – Nội dung trao đổi ban đầu (ví dụ: yêu cầu tư vấn, ghi chú…)
- `channel` *(string, nullable, enum)* – Kênh conversation, một trong: `web`, `facebook`, `zalo` (mặc định `web` nếu không gửi)
- `sender` *(string, nullable, enum)* – Người gửi message, một trong: `ai`, `lead`, `advisor` (mặc định `lead` nếu không gửi)

### 2.3. Ví dụ request (cURL)

```bash
curl -X POST "{BASE_URL}/lead-create" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Nguyễn Văn A",
    "phone": "0901234567",
    "email": "a@example.com",
    "interest_course": "Cử nhân CNTT",
    "source": "website",
    "assigned_to": 2,
    "message": "Khách để lại form từ landing page CNTT.",
    "channel": "web",
    "sender": "lead",
    "school_id": 1
  }'
```

**Response thành công (rút gọn)**

```json
{
  "id": 123,
  "assigned_to": 2,
  "name": "Nguyễn Văn A",
  "phone": "0901234567",
  "email": "a@example.com",
  "interest_course": "Cử nhân CNTT",
  "source": "website",
  "status": "new",
  "year_of_admission": 2026,
  "created_at": "2026-03-12T10:00:00.000000Z",
  "updated_at": "2026-03-12T10:00:00.000000Z"
}
```

> Lưu ý: Nếu gửi `message`, hệ thống sẽ tự tạo 1 `conversation` (channel = `channel` hoặc `crm`) và 1 `message` đầu tiên gắn với lead này.

---

## 3. API tạo Hồ sơ xét tuyển (Admission Profile) từ Lead

Có **2 cách** tuỳ vào mức độ tin cậy của hệ thống bên ngoài:

1. Dùng **Bearer token** (authenticated CRM user).
2. Dùng **Static API Key** qua nhóm `/external` (không cần login).

### 3.1. Tạo hồ sơ từ Lead – dùng Bearer token

**Endpoint**

- `POST {BASE_URL}/leads/{leadId}/admission-profile`
- Yêu cầu header **Bearer token**.

Ngoài ra, nếu bạn muốn truyền `lead_id` trong body (không đặt trên URL), có thể dùng:

- `POST {BASE_URL}/admission-profiles/from-lead`

**Headers**

- `Authorization: Bearer {access_token}`
- `Content-Type: application/json`
- `Accept: application/json`

**Path params**

- `leadId` – ID lead đã tồn tại trong CRM.

**Body (JSON) – đầy đủ các trường trong DB**

Tất cả các trường dưới đây đều là **optional** (trừ `lead_id` nếu bạn dùng endpoint `/admission-profiles/from-lead`), hệ thống sẽ lưu những gì bạn gửi:

- `lead_id` *(int)* – **bắt buộc** khi gọi `POST {BASE_URL}/admission-profiles/from-lead`
- `identification_number` *(string, nullable)* – Số CMND/CCCD (DB cho phép `null`, unique)
- `province` *(string, nullable)* – Tỉnh/Thành phố
- `graduation_year` *(int|year, nullable)* – Năm tốt nghiệp (ví dụ `2024`)
- `academic_level` *(string, nullable)* – Học lực (ví dụ: `Giỏi`, `Khá`, `Trung bình`)
- `gpa` *(number, nullable)* – Điểm trung bình (0–10, lưu dạng decimal)
- `admission_method` *(string, nullable)* – Hình thức xét tuyển (ví dụ: `xet-hoc-ba`, `diem-thi`, `lien-thong`)
- `academic_records` *(array hoặc JSON-encoded string, nullable)* – Dữ liệu điểm các môn (mảng `{subject, score}`)
- `document_status` *(string, nullable)* – Trạng thái hồ sơ. Nếu không gửi, hệ thống mặc định `registered` khi tạo từ CRM.
  - Giá trị hỗ trợ : `not_registered`, `registered`, `submitted`, `need_more_docs`, `valid`, `in_review`, `admitted`, `confirmed`, `enrolled`
  - Hỗ trợ trạng thái: `pending`, `verified`, `rejected`
- `admin_note` *(string, nullable)* – Ghi chú cán bộ (nếu phía tích hợp muốn đẩy ghi chú)
- `admission_file` *(string, nullable)* – Đường dẫn file hồ sơ (field này thường do CRM upload và tự lưu, tích hợp ngoài thường không cần gửi)

Ví dụ đơn giản:

```bash
curl -X POST "{BASE_URL}/leads/123/admission-profile" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "identification_number": "012345678901",
    "academic_records": [
      { "year": 2023, "gpa": 8.2 },
      { "year": 2024, "gpa": 8.5 }
    ]
  }'
```

Ví dụ (truyền `lead_id` trong body):

```bash
curl -X POST "{BASE_URL}/admission-profiles/from-lead" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "lead_id": 123,
    "identification_number": "012345678901",
    "province": "Hà Nội",
    "graduation_year": 2024,
    "academic_level": "Giỏi",
    "gpa": 8.30,
    "admission_method": "xet-hoc-ba",
    "academic_records": [
      { "year": 2023, "gpa": 8.2 },
      { "year": 2024, "gpa": 8.5 }
    ]
  }'
```

> Lưu ý:
> - Nếu lead đã có hồ sơ rồi, API sẽ trả lỗi `422`.
> - Khi tạo hồ sơ, hệ thống sẽ tự chuyển `status` của lead sang `considering`.

### 3.2. Tạo hồ sơ xét tuyển – dùng Static API Key (external)

Nhóm route này dành riêng cho **hệ thống external** sử dụng `X-API-KEY` mà không cần đăng nhập.

**Endpoint**

- `POST {BASE_URL}/external/admission-profiles/{leadId}`

**Headers**

- `X-API-KEY: {YOUR_STATIC_API_KEY}`
- `Content-Type: application/json`
- `Accept: application/json`

**Body**

Tuỳ vào implement trong `AdmissionProfileController@create`, ví dụ:

- `identification_number` *(string, nullable)*
- `academic_records` *(array hoặc JSON-encoded string)*
- Các trường khác (nếu được hỗ trợ): `province`, `graduation_year`, `academic_level`, `gpa`, `admission_method`, v.v.

**Ví dụ cURL**

```bash
curl -X POST "{BASE_URL}/external/admission-profiles/123" \
  -H "X-API-KEY: {YOUR_STATIC_API_KEY}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "identification_number": "012345678901",
    "province": "Hà Nội",
    "graduation_year": 2024,
    "academic_level": "THPT",
    "gpa": 8.3,
    "admission_method": "xet-hoc-ba",
    "academic_records": [
      { "subject": "Toán", "score": 8.5 },
      { "subject": "Lý", "score": 8.0 }
    ]
  }'
```

---

## 4. API lấy danh sách Trường (Schools) – Bearer token

### 4.1. Endpoint

- `GET {BASE_URL}/schools`

### 4.2. Headers

- `Authorization: Bearer {access_token}`
- `Accept: application/json`

### 4.3. Query params (tùy chọn)

- `search` *(string)* – tìm theo `name` hoặc `domain`

### 4.4. Ví dụ request (cURL)

```bash
curl -X GET "{BASE_URL}/schools?search=aratech" \
  -H "Authorization: Bearer {access_token}" \
  -H "Accept: application/json"
```

### 4.5. Response mẫu

```json
{
  "data": [
    {
      "id": 1,
      "name": "Trường A",
      "domain": "truonga.edu.vn",
      "contact_email": "contact@truonga.edu.vn"
    }
  ]
}
```

## 4. Gợi ý flow tích hợp điển hình

1. **Hệ thống bên ngoài gửi lead mới**
   - Đăng nhập 1 lần để lấy `access_token` (reuse nhiều lần tới khi hết hạn).
   - Gọi `POST {BASE_URL}/lead-create` với thông tin ứng viên + `message` ghi nhận nguồn/yêu cầu.
   - Lưu lại `leadId` trả về nếu cần dùng sau.

2. **Khi ứng viên đủ điều kiện mở hồ sơ**
   - Dùng `leadId` đã lưu để gọi:
     - Hoặc `POST {BASE_URL}/leads/{leadId}/admission-profile` (Bearer token)
     - Hoặc `POST {BASE_URL}/external/admission-profiles/{leadId}` (X-API-KEY)

Nếu bạn cần mình bổ sung thêm phần **response mẫu chi tiết** cho API hồ sơ (bao gồm đầy đủ field), mình có thể mở rộng thêm mục 3 với JSON thực tế từ hệ thống.  

