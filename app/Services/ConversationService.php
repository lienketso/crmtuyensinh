<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

class ConversationService
{
    /**
     * Lấy tất cả conversations của một lead
     */
    public function getConversationsByLead(int $leadId): Collection
    {
        return Conversation::where('lead_id', $leadId)
            ->with('messages')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy messages của một conversation
     */
    public function getMessagesByConversation(int $conversationId): Collection
    {
        return Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Tạo conversation mới
     */
    public function createConversation(int $leadId, string $channel = 'web'): Conversation
    {
        return Conversation::create([
            'lead_id' => $leadId,
            'channel' => $channel,
            'created_at' => now(),
        ]);
    }

    /**
     * Tạo message mới
     */
    public function createMessage(int $conversationId, string $sender, string $content): Message
    {
        return Message::create([
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'content' => $content,
            'created_at' => now(),
        ]);
    }
}
