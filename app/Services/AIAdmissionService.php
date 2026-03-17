<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAdmissionService
{
    private string $basePrompt;

    public function __construct()
    {
        $this->basePrompt = "Bạn là nhân viên tuyển sinh chuyên nghiệp của một cơ sở giáo dục.
Nhiệm vụ của bạn:
- Tư vấn chương trình học phù hợp
- Giải thích rõ ràng, dễ hiểu
- Đặt câu hỏi khai thác nhu cầu
- Kết thúc bằng lời mời để lại thông tin hoặc đăng ký học

Không trả lời lan man.
Không hứa hẹn quá mức.
Luôn giữ giọng điệu chuyên nghiệp và thân thiện.";
    }

    /**
     * Xử lý tin nhắn từ lead và trả về phản hồi AI
     */
    public function processMessage(string $message, ?int $conversationId = null): array
    {
        // Single-tenant: load tất cả khoá học
        $courses = Course::query()->get();
        $coursesContext = $courses->map(function ($course) {
            return "{$course->name}: {$course->description} - Học phí: " . number_format($course->tuition_fee ?? 0) . " VNĐ - Thời gian: {$course->duration}";
        })->join("\n");

        // Tạo prompt với context
        $systemPrompt = $this->basePrompt . "\n\nDanh sách các khoá học hiện có:\n" . $coursesContext;

        // Gọi OpenAI API (hoặc Claude)
        $response = $this->callAI($systemPrompt, $message);

        return [
            'response' => $response,
            'conversation_id' => $conversationId,
        ];
    }

    /**
     * Gọi AI API (OpenAI hoặc Claude)
     */
    private function callAI(string $systemPrompt, string $userMessage): string
    {
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            return "Xin lỗi, hệ thống AI đang được bảo trì. Vui lòng liên hệ trực tiếp với chúng tôi qua số điện thoại.";
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'Xin lỗi, tôi không thể trả lời câu hỏi này.';
            }

            Log::error('OpenAI API Error', ['response' => $response->body()]);
            return "Xin lỗi, có lỗi xảy ra khi xử lý yêu cầu của bạn.";
        } catch (\Exception $e) {
            Log::error('AI Service Exception', ['error' => $e->getMessage()]);
            return "Xin lỗi, hệ thống đang gặp sự cố. Vui lòng thử lại sau.";
        }
    }

    /**
     * Tạo hoặc lấy conversation hiện tại
     */
    public function getOrCreateConversation(int $leadId, string $channel = 'web'): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'lead_id' => $leadId,
                'channel' => $channel,
            ],
            [
                'created_at' => now(),
            ]
        );
    }

    /**
     * Lưu message vào database
     */
    public function saveMessage(int $conversationId, string $sender, string $content): Message
    {
        return Message::create([
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'content' => $content,
            'created_at' => now(),
        ]);
    }
}
