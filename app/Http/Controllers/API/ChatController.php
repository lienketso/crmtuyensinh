<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AIAdmissionService;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __construct(
        private AIAdmissionService $aiService,
        private LeadService $leadService
    ) {}

    /**
     * Xử lý chat message từ lead
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'lead_id' => 'nullable|exists:leads,id',
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'channel' => 'nullable|in:web,facebook,zalo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = $request->message;
        $channel = $request->channel ?? 'web';

        // Tạo hoặc lấy lead
        $lead = null;
        if ($request->lead_id) {
            $lead = $this->leadService->getLeadById($request->lead_id);
        }

        if (!$lead && ($request->phone || $request->email)) {
            // Tạo lead mới nếu có thông tin
            $lead = $this->leadService->createLead([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'source' => $channel,
            ]);
        }

        // Lấy hoặc tạo conversation
        $conversation = null;
        if ($lead) {
            $conversation = $this->aiService->getOrCreateConversation($lead->id, $channel);
        }

        // Lưu message từ lead
        if ($conversation) {
            $this->aiService->saveMessage($conversation->id, 'lead', $message);
        }

        // Xử lý AI response
        $aiResponse = $this->aiService->processMessage($message, $conversation?->id);

        // Lưu AI response
        if ($conversation) {
            $this->aiService->saveMessage($conversation->id, 'ai', $aiResponse['response']);
        }

        return response()->json([
            'response' => $aiResponse['response'],
            'lead_id' => $lead?->id,
            'conversation_id' => $conversation?->id,
        ]);
    }
}
