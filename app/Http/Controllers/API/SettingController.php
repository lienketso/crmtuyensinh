<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * Lấy cấu hình CRM (chỉ cho admin).
     */
    public function index(Request $request)
    {
        $settings = Setting::query()
            ->pluck('value', 'key');

        $get = function (string $key, mixed $default = null) use ($settings) {
            return $settings[$key] ?? $default;
        };

        $logoPath = $get('site.logo');
        $faviconPath = $get('site.favicon');

        return response()->json([
            'site_name' => $get('site.name', 'AI Tuyển Sinh'),
            'logo' => $logoPath,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'favicon' => $faviconPath,
            'favicon_url' => $faviconPath ? Storage::disk('public')->url($faviconPath) : null,

            'mail_default' => $get('mail.default', config('mail.default')),
            'mail_from_name' => $get('mail.from.name', config('mail.from.name')),
            'mail_from_address' => $get('mail.from.address', config('mail.from.address')),
            'mail_host' => $get('mail.smtp.host', config('mail.mailers.smtp.host')),
            'mail_port' => (int) $get('mail.smtp.port', config('mail.mailers.smtp.port')),
            'mail_encryption' => $get('mail.smtp.scheme', config('mail.mailers.smtp.scheme')),
            'mail_username' => $get('mail.smtp.username', config('mail.mailers.smtp.username')),
            // Không trả password ra ngoài, chỉ báo đã cấu hình hay chưa
            'mail_has_password' => $get('mail.smtp.password') ? true : false,
            'mail_lead_recipient' => $get('mail.lead_recipient', null),
        ]);
    }

    /**
     * Cập nhật cấu hình CRM (logo, favicon, mail...).
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,svg|max:1024',

            'mail_default' => 'nullable|string|in:smtp,log,sendmail,failover,roundrobin,array',
            'mail_from_name' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|string|max:20',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_lead_recipient' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Helper lưu setting
        $set = function (string $key, mixed $value, ?string $type = null, ?string $group = null) {
            if ($value === null || $value === '') {
                return;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => $group,
                ]
            );
        };

        if (isset($data['site_name'])) {
            $set('site.name', $data['site_name'], 'string', 'site');
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('settings', 'public');
            $set('site.logo', $path, 'file', 'site');
        }

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $path = $file->store('settings', 'public');
            $set('site.favicon', $path, 'file', 'site');
        }

        if (isset($data['mail_default'])) {
            $set('mail.default', $data['mail_default'], 'string', 'mail');
        }
        if (isset($data['mail_from_name'])) {
            $set('mail.from.name', $data['mail_from_name'], 'string', 'mail');
        }
        if (isset($data['mail_from_address'])) {
            $set('mail.from.address', $data['mail_from_address'], 'string', 'mail');
        }
        if (isset($data['mail_host'])) {
            $set('mail.smtp.host', $data['mail_host'], 'string', 'mail');
        }
        if (isset($data['mail_port'])) {
            $set('mail.smtp.port', (string) $data['mail_port'], 'integer', 'mail');
        }
        if (isset($data['mail_encryption'])) {
            $set('mail.smtp.scheme', $data['mail_encryption'], 'string', 'mail');
        }
        if (isset($data['mail_username'])) {
            $set('mail.smtp.username', $data['mail_username'], 'string', 'mail');
        }
        // Password: chỉ cập nhật khi người dùng nhập (không gửi để giữ nguyên)
        if (array_key_exists('mail_password', $data) && $data['mail_password'] !== null && $data['mail_password'] !== '') {
            $set('mail.smtp.password', $data['mail_password'], 'string', 'mail');
        }

        if (isset($data['mail_lead_recipient'])) {
            $set('mail.lead_recipient', $data['mail_lead_recipient'], 'string', 'mail');
        }

        return $this->index($request);
    }

    /**
     * Gửi email test với cấu hình mail hiện tại.
     */
    public function testMail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = Setting::query()->pluck('value', 'key');
        $get = function (string $key, mixed $default = null) use ($settings) {
            return $settings[$key] ?? $default;
        };

        $to = $request->input('to')
            ?: $get('mail.lead_recipient')
            ?: $get('mail.from.address')
            ?: config('mail.from.address');

        if (! $to) {
            return response()->json([
                'message' => 'Chưa cấu hình địa chỉ email nhận test (mail_from_address hoặc mail_lead_recipient).',
            ], 422);
        }

        $subject = 'Test cấu hình SMTP - CRM Tuyển sinh';

        $bodyLines = [
            'Đây là email test từ hệ thống CRM Tuyển sinh.',
            '',
            'Nếu bạn nhận được email này, cấu hình SMTP hiện tại đang hoạt động.',
        ];

        try {
            Mail::raw(implode("\n", $bodyLines), function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Test mail failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gửi email test thất bại. Vui lòng kiểm tra lại cấu hình SMTP.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Đã gửi email test thành công tới: ' . $to,
        ]);
    }
}

