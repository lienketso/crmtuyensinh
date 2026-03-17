<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadNotificationService
{
    /**
     * Gửi email thông báo khi có lead mới.
     */
    public function notifyNewLead(Lead $lead): void
    {
        $to = config('mail.lead_recipient') ?: config('mail.from.address');

        if (! $to) {
            return;
        }

        $subject = 'Lead mới: ' . ($lead->name ?: ($lead->phone ?: 'Không tên'));

        $bodyLines = [
            'Có một lead mới được tạo trong hệ thống CRM Tuyển sinh.',
            '',
            'Thông tin lead:',
            '- Tên: ' . ($lead->name ?: '—'),
            '- Số điện thoại: ' . ($lead->phone ?: '—'),
            '- Email: ' . ($lead->email ?: '—'),
            '- Khoá học quan tâm: ' . ($lead->interest_course ?: '—'),
            '- Nguồn: ' . ($lead->source ?: '—'),
            '- Trạng thái: ' . ($lead->status ?: '—'),
        ];

        $appUrl = config('app.url');
        if ($appUrl) {
            $bodyLines[] = '';
            $bodyLines[] = 'Xem chi tiết trong CRM: ' . rtrim($appUrl, '/') . '/#/leads/' . $lead->id;
        }

        $body = implode("\n", $bodyLines);

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to send new lead notification', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

