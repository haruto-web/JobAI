<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeleteChatMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        if (Cache::has('delete_chat_messages_' . $this->userId)) {
            ChatMessage::where('user_id', $this->userId)->delete();
            Cache::forget('delete_chat_messages_' . $this->userId);
            Cache::forget('job_draft_' . $this->userId);
            Log::info('Chat messages deleted for user after logout', ['user_id' => $this->userId]);
        }
    }
}
